<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Country;
use App\Models\Category;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Actions\Action;
use App\Filament\Widgets\ItemSettingsWidget;
use Illuminate\Support\Str;

class ListItems extends ListRecords
{
	protected static string $resource = ItemResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
			$this->createApprovalAction(),
			$this->createPaymentAction(),
		];
	}

	protected function getHeaderWidgets(): array
	{
		return [
			ItemSettingsWidget::class,
		];
	}

	public function getTabs(): array
	{
		// Get all countries that have items
		$countries = Country::whereHas('items')->get();

		$tabs = [
			'all' => Tab::make('All')
				->badge(Item::count()),
		];

		// Add a tab for each country
		foreach ($countries as $country) {
			$tabs[strtolower($country->name)] = Tab::make($country->name)
				->badge(Item::whereHas('country', function ($query) use ($country) {
					$query->where('id', $country->id);
				})->count())
				->modifyQueryUsing(fn($query) => $query->whereHas('country', function ($query) use ($country) {
					$query->where('id', $country->id);
				}));
		}

		return $tabs;
	}



	protected function createApprovalAction(): Action
	{
		return Action::make('toggle_listing_approval')
			->label('Toggle Listing Approval')
			->icon('heroicon-o-check-circle')
			->color('primary')
			->outlined()
			->modalHeading('Toggle Listing Approval')
			->modalDescription('Select a country and/or a category to toggle approval requirement for listings.')
			->modalWidth('md')
			->form([
				\Filament\Forms\Components\Select::make('country')
					->label('Country')
					->options([
						'' => '— No Country —',
						'all' => 'All Countries (Global)',
						...Country::pluck('name', 'name')->toArray(),
					])
					->native(false)
					->nullable()
					->default(''),
				\Filament\Forms\Components\Select::make('category')
					->label('Category (optional)')
					->native(false)
					->options([
						'' => '— No Category —',
						'all' => 'All Categories (Global)',
						...\App\Models\Category::pluck('name', 'name')->toArray(),
					])
					->nullable()
					->default(''),
				\Filament\Forms\Components\Toggle::make('require_approval')
					->label('Require Approval')
					->helperText('Enable this to require approval for new listings in the selected target.')
					->default(false),
			])
			->action(function (array $data) {
				$country = $data['country'];
				$category = $data['category'] ?? '';
				$requireApproval = $data['require_approval'];

				if (empty($country) && empty($category)) {
					throw \Illuminate\Validation\ValidationException::withMessages([
						'country' => 'Please select a country or category.',
					]);
				}

				if (!empty($category)) {
					if ($category === 'all') {
						$settingKey = 'require_listing_approval_for_all';
						$settingName = 'Require Listing Approval For All';
					} else {
						$slug = Str::slug($category);
						$settingKey = 'require_listing_approval_for_category_' . $slug;
						$settingName = 'Require Listing Approval For Category ' . $category;
					}
				} else {
					$settingKey = 'require_listing_approval_for_' . strtolower($country);
					$settingName = 'Require Listing Approval For ' . ucfirst($country);
				}

				Setting::updateOrCreate(
					['key_slug' => $settingKey],
					[
						'key_name' => $settingName,
						'value' => $requireApproval ? 'true' : 'false'
					]
				);

				\Filament\Notifications\Notification::make()
					->title('Approval settings updated')
					->body('Approval requirement ' . ($requireApproval ? 'enabled' : 'disabled'))
					->success()
					->send();
			});
	}

	protected function createPaymentAction(): Action
	{
		return Action::make('toggle_payment_requirement')
			->label('Toggle Payment Requirement')
			->icon('heroicon-o-credit-card')
			->color('warning')
			->outlined()
			->modalHeading('Toggle Payment Requirement')
			->modalDescription('Select a country and/or a category to toggle payment requirement for listings.')
			->modalWidth('md')
			->form([
				\Filament\Forms\Components\Select::make('country')
					->label('Country')
					->options([
						'' => '— No Country —',
						'all' => 'All Countries (Global)',
						...Country::pluck('name', 'name')->toArray(),
					])
					->native(false)
					->nullable()
					->default(''),
				\Filament\Forms\Components\Select::make('category')
					->label('Category (optional)')
					->native(false)
					->options([
						'' => '— No Category —',
						'all' => 'All Categories (Global)',
						...\App\Models\Category::pluck('name', 'name')->toArray(),
					])
					->nullable()
					->default(''),
				\Filament\Forms\Components\Toggle::make('require_payment')
					->label('Require Payment')
					->helperText('Enable this to require payment for new listings in the selected target.')
					->default(false),
			])
			->action(function (array $data) {
				$country = $data['country'];
				$category = $data['category'] ?? '';
				$requirePayment = $data['require_payment'];

				if (empty($country) && empty($category)) {
					throw \Illuminate\Validation\ValidationException::withMessages([
						'country' => 'Please select a country or category.',
					]);
				}

				if (!empty($category)) {
					if ($category === 'all') {
						$settingKey = 'require_payment_for_all';
						$settingName = 'Require Payment For All';
					} else {
						$slug = Str::slug($category);
						$settingKey = 'require_payment_for_category_' . $slug;
						$settingName = 'Require Payment For Category ' . $category;
					}
				} else {
					$settingKey = 'require_payment_for_' . strtolower($country);
					$settingName = 'Require Payment For ' . ucfirst($country);
				}

				Setting::updateOrCreate(
					['key_slug' => $settingKey],
					[
						'key_name' => $settingName,
						'value' => $requirePayment ? 'true' : 'false'
					]
				);

				\Filament\Notifications\Notification::make()
					->title('Payment settings updated')
					->body('Payment requirement ' . ($requirePayment ? 'enabled' : 'disabled'))
					->success()
					->send();
			});
	}
}
