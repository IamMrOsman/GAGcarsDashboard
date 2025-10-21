<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Country;
use App\Models\Category;
use App\Models\CategoryRequirement;
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

		$tabs['pending_approval'] = Tab::make('Pending Approval')
			->badge(Item::where('status', 'pending_approval')->count())
			->modifyQueryUsing(fn($query) => $query->where('status', 'pending_approval'));
		$tabs['active'] = Tab::make('Active')
			->badge(Item::where('status', 'active')->count())
			->modifyQueryUsing(fn($query) => $query->where('status', 'active'));
		$tabs['rejected'] = Tab::make('Rejected')
			->badge(Item::where('status', 'rejected')->count())
			->modifyQueryUsing(fn($query) => $query->where('status', 'rejected'));
		$tabs['sold'] = Tab::make('Sold')
			->badge(Item::where('status', 'sold')->count())
			->modifyQueryUsing(fn($query) => $query->where('status', 'sold'));

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
			->modalDescription('Select a country and categories to toggle approval requirement for listings.')
			->modalWidth('lg')
			->form([
				\Filament\Forms\Components\Select::make('country_id')
					->label('Country')
					->options(Country::pluck('name', 'id')->toArray())
					->native(false)
					->required()
					->searchable()
					->live()
					->afterStateUpdated(function ($state, callable $set) {
						if ($state) {
							// Get existing category requirements for this country
							$existingRequirements = CategoryRequirement::where('country_id', $state)
								->where('require_approval', true)
								->pluck('category_id')
								->toArray();
							$set('category_ids', $existingRequirements);
						} else {
							$set('category_ids', []);
						}
					}),
				\Filament\Forms\Components\Select::make('category_ids')
					->label('Categories')
					->options(function (callable $get) {
						$countryId = $get('country_id');
						if (!$countryId) {
							return [];
						}
						return Category::pluck('name', 'id')->toArray();
					})
					->native(false)
					->multiple()
					->searchable()
					->placeholder('Select categories...')
					->helperText('Select multiple categories to apply approval requirements. Previously selected categories will be shown when you select a country.'),
				\Filament\Forms\Components\Toggle::make('require_approval')
					->label('Require Approval')
					->helperText('Enable this to require approval for new listings in the selected country and categories.')
					->default(true),
			])
			->action(function (array $data) {
				$countryId = $data['country_id'];
				$categoryIds = $data['category_ids'] ?? [];
				$requireApproval = $data['require_approval'];

				if (empty($categoryIds)) {
					throw \Illuminate\Validation\ValidationException::withMessages([
						'category_ids' => 'Please select at least one category.',
					]);
				}

				$country = Country::find($countryId);
				$updatedCategories = [];
				$removedCategories = [];

				// Get existing requirements for this country
				$existingRequirements = CategoryRequirement::where('country_id', $countryId)
					->where('require_approval', true)
					->pluck('category_id')
					->toArray();

				// Update or create records for selected categories
				foreach ($categoryIds as $categoryId) {
					CategoryRequirement::updateOrCreate(
						[
							'country_id' => $countryId,
							'category_id' => $categoryId,
						],
						[
							'require_approval' => $requireApproval,
						]
					);
					$updatedCategories[] = Category::find($categoryId)->name;
				}

				// Remove approval requirement from categories that were previously selected but are no longer selected
				$categoriesToRemove = array_diff($existingRequirements, $categoryIds);
				foreach ($categoriesToRemove as $categoryId) {
					CategoryRequirement::where('country_id', $countryId)
						->where('category_id', $categoryId)
						->update(['require_approval' => false]);
					$removedCategories[] = Category::find($categoryId)->name;
				}

				$message = "Approval requirement " . ($requireApproval ? 'enabled' : 'disabled') . " for {$country->name}";
				if (!empty($updatedCategories)) {
					$message .= " - Categories: " . implode(', ', $updatedCategories);
				}
				if (!empty($removedCategories)) {
					$message .= " - Removed from: " . implode(', ', $removedCategories);
				}

				\Filament\Notifications\Notification::make()
					->title('Approval settings updated')
					->body($message)
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
			->modalDescription('Select a country and categories to toggle payment requirement for listings.')
			->modalWidth('lg')
			->form([
				\Filament\Forms\Components\Select::make('country_id')
					->label('Country')
					->options(Country::pluck('name', 'id')->toArray())
					->native(false)
					->required()
					->searchable()
					->live()
					->afterStateUpdated(function ($state, callable $set) {
						if ($state) {
							// Get existing category requirements for this country
							$existingRequirements = CategoryRequirement::where('country_id', $state)
								->where('require_payment', true)
								->pluck('category_id')
								->toArray();
							$set('category_ids', $existingRequirements);
						} else {
							$set('category_ids', []);
						}
					}),
				\Filament\Forms\Components\Select::make('category_ids')
					->label('Categories')
					->options(function (callable $get) {
						$countryId = $get('country_id');
						if (!$countryId) {
							return [];
						}
						return Category::pluck('name', 'id')->toArray();
					})
					->native(false)
					->multiple()
					->searchable()
					->placeholder('Select categories...')
					->helperText('Select multiple categories to apply payment requirements. Previously selected categories will be shown when you select a country.'),
				\Filament\Forms\Components\Toggle::make('require_payment')
					->label('Require Payment')
					->helperText('Enable this to require payment for new listings in the selected country and categories.')
					->default(true),
			])
			->action(function (array $data) {
				$countryId = $data['country_id'];
				$categoryIds = $data['category_ids'] ?? [];
				$requirePayment = $data['require_payment'];

				if (empty($categoryIds)) {
					throw \Illuminate\Validation\ValidationException::withMessages([
						'category_ids' => 'Please select at least one category.',
					]);
				}

				$country = Country::find($countryId);
				$updatedCategories = [];
				$removedCategories = [];

				// Get existing requirements for this country
				$existingRequirements = CategoryRequirement::where('country_id', $countryId)
					->where('require_payment', true)
					->pluck('category_id')
					->toArray();

				// Update or create records for selected categories
				foreach ($categoryIds as $categoryId) {
					CategoryRequirement::updateOrCreate(
						[
							'country_id' => $countryId,
							'category_id' => $categoryId,
						],
						[
							'require_payment' => $requirePayment,
						]
					);
					$updatedCategories[] = Category::find($categoryId)->name;
				}

				// Remove payment requirement from categories that were previously selected but are no longer selected
				$categoriesToRemove = array_diff($existingRequirements, $categoryIds);
				foreach ($categoriesToRemove as $categoryId) {
					CategoryRequirement::where('country_id', $countryId)
						->where('category_id', $categoryId)
						->update(['require_payment' => false]);
					$removedCategories[] = Category::find($categoryId)->name;
				}

				$message = "Payment requirement " . ($requirePayment ? 'enabled' : 'disabled') . " for {$country->name}";
				if (!empty($updatedCategories)) {
					$message .= " - Categories: " . implode(', ', $updatedCategories);
				}
				if (!empty($removedCategories)) {
					$message .= " - Removed from: " . implode(', ', $removedCategories);
				}

				\Filament\Notifications\Notification::make()
					->title('Payment settings updated')
					->body($message)
					->success()
					->send();
			});
	}
}
