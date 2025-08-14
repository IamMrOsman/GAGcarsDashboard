<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Country;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Actions\Action;
use App\Filament\Widgets\ItemSettingsWidget;

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
			->modalDescription('Select a country and toggle approval requirement for listings.')
			->modalWidth('md')
			->form([
				\Filament\Forms\Components\Select::make('target')
					->label('Target')
					->options([
						'all' => 'All Countries (Global)',
						...Country::pluck('name', 'name')->toArray(),
					])
					->required()
					->default('all'),
				\Filament\Forms\Components\Toggle::make('require_approval')
					->label('Require Approval')
					->helperText('Enable this to require approval for new listings in the selected target.')
					->default(function () {
						$target = request()->get('target', 'all');
						$settingKey = "require_listing_approval_for_" . strtolower($target);
						$currentSetting = Setting::where('key_slug', $settingKey)->first();
						return $currentSetting ? $currentSetting->value === 'true' : false;
					}),
			])
			->action(function (array $data) {
				$target = $data['target'];
				$settingKey = "require_listing_approval_for_" . strtolower($target);
				$settingName = "Require Listing Approval For " . ucfirst($target);
				$requireApproval = $data['require_approval'];

				Setting::updateOrCreate(
					['key_slug' => $settingKey],
					[
						'key_name' => $settingName,
						'value' => $requireApproval ? 'true' : 'false'
					]
				);

				// Use Filament's notification system
				\Filament\Notifications\Notification::make()
					->title("Approval settings updated for " . ucfirst($target))
					->body("Approval requirement " . ($requireApproval ? 'enabled' : 'disabled'))
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
			->modalDescription('Select a country and toggle payment requirement for listings.')
			->modalWidth('md')
			->form([
				\Filament\Forms\Components\Select::make('target')
					->label('Target')
					->options([
						'all' => 'All Countries (Global)',
						...Country::pluck('name', 'name')->toArray(),
					])
					->required()
					->default('all'),
				\Filament\Forms\Components\Toggle::make('require_payment')
					->label('Require Payment')
					->helperText('Enable this to require payment for new listings in the selected target.')
					->default(function () {
						$target = request()->get('target', 'all');
						$settingKey = "require_payment_for_" . strtolower($target);
						$currentSetting = Setting::where('key_slug', $settingKey)->first();
						return $currentSetting ? $currentSetting->value === 'true' : false;
					}),
			])
			->action(function (array $data) {
				$target = $data['target'];
				$settingKey = "require_payment_for_" . strtolower($target);
				$settingName = "Require Payment For " . ucfirst($target);
				$requirePayment = $data['require_payment'];

				Setting::updateOrCreate(
					['key_slug' => $settingKey],
					[
						'key_name' => $settingName,
						'value' => $requirePayment ? 'true' : 'false'
					]
				);

				// Use Filament's notification system
				\Filament\Notifications\Notification::make()
					->title("Payment settings updated for " . ucfirst($target))
					->body("Payment requirement " . ($requirePayment ? 'enabled' : 'disabled'))
					->success()
					->send();
			});
	}
}
