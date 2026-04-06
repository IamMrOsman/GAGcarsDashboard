<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Models\Setting;
use App\Filament\Clusters\Settings;
use App\Services\ListingSettingsService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageListingSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-clock';

	protected static string $view = 'filament.clusters.settings.pages.manage-listing-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Listing expiry';

	protected static ?string $title = 'Listing expiry';

	protected static ?string $slug = 'listing-expiry';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Listing lifetime')
					->description('How long a listing stays public after it becomes Active. Changing this applies to new activations only, not existing expiry dates.')
					->schema([
						TextInput::make('listing_active_days')
							->label('Days until listing expires')
							->numeric()
							->required()
							->minValue(ListingSettingsService::MIN_DAYS)
							->maxValue(ListingSettingsService::MAX_DAYS)
							->default(30)
							->helperText('Countdown starts when the listing status becomes Active (including after admin approval).'),
					]),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			$data = $this->form->getState();
			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->danger()
					->send();

				return;
			}

			Setting::updateOrCreate(
				['key_slug' => 'listing'],
				[
					'key_name' => 'Listing expiry',
					'key_slug' => 'listing',
					'value' => 'configured',
					'description' => 'Days active listings remain before expiring',
					'data' => $data,
				]
			);

			Notification::make()
				->title('Listing settings saved')
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('Listing settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'listing')->first();
		$saved = ($row && is_array($row->data)) ? $row->data : [];

		$defaults = [
			'listing_active_days' => ListingSettingsService::getActiveListingDays(),
		];

		return array_merge($defaults, $saved);
	}
}
