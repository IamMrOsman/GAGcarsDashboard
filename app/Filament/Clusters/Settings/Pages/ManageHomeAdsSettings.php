<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Setting;
use App\Services\HomeAdsSettingsService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageHomeAdsSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-megaphone';

	protected static string $view = 'filament.clusters.settings.pages.manage-home-ads-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Homepage ads';

	protected static ?string $title = 'Homepage ads';

	protected static ?string $slug = 'homepage-ads';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Inline featured distribution')
					->description('Controls how promoted items (active promotions) are injected into the homepage feed.')
					->schema([
						Toggle::make('enabled')
							->label('Enable homepage ads injection')
							->default(false),

						TextInput::make('inject_every_n')
							->label('Inject 1 featured after every N organic items')
							->numeric()
							->minValue(HomeAdsSettingsService::MIN_INJECT_EVERY_N)
							->maxValue(HomeAdsSettingsService::MAX_INJECT_EVERY_N)
							->default(8)
							->required(),

						TextInput::make('max_featured_per_page')
							->label('Max featured insertions per page')
							->numeric()
							->minValue(HomeAdsSettingsService::MIN_MAX_FEATURED_PER_PAGE)
							->maxValue(HomeAdsSettingsService::MAX_MAX_FEATURED_PER_PAGE)
							->default(3)
							->required(),

						Toggle::make('no_adjacent_featured')
							->label('Prevent adjacent featured items')
							->default(true),

						Toggle::make('dedupe_within_page')
							->label('Do not repeat the same item within a page')
							->default(true),

						TextInput::make('rotation_seed_window_minutes')
							->label('Rotation stability window (minutes)')
							->numeric()
							->minValue(HomeAdsSettingsService::MIN_SEED_WINDOW_MINUTES)
							->maxValue(HomeAdsSettingsService::MAX_SEED_WINDOW_MINUTES)
							->default(60)
							->helperText('Within this time window, users get a stable ordering to avoid excessive reshuffles while scrolling.')
							->required(),
					])
					->columns(2),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			$data = $this->form->getState();

			Setting::updateOrCreate(
				['key_slug' => 'home_ads'],
				[
					'key_name' => 'Homepage ads',
					'key_slug' => 'home_ads',
					'value' => 'configured',
					'description' => 'Homepage featured/promoted distribution settings',
					'data' => $data,
				]
			);

			Notification::make()
				->title('Homepage ads settings saved')
				->success()
				->send();
		} catch (\Throwable $e) {
			\Log::error('Homepage ads settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving homepage ads settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'home_ads')->first();
		$saved = ($row && is_array($row->data)) ? $row->data : [];

		return array_merge([
			'enabled' => (bool) ($saved['enabled'] ?? false),
			'inject_every_n' => (int) ($saved['inject_every_n'] ?? 8),
			'max_featured_per_page' => (int) ($saved['max_featured_per_page'] ?? 3),
			'no_adjacent_featured' => (bool) ($saved['no_adjacent_featured'] ?? true),
			'dedupe_within_page' => (bool) ($saved['dedupe_within_page'] ?? true),
			'rotation_seed_window_minutes' => (int) ($saved['rotation_seed_window_minutes'] ?? 60),
		], $saved);
	}
}

