<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Setting;
use App\Services\HomeFeedSettingsService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageHomeFeedSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-bars-arrow-down';

	protected static string $view = 'filament.clusters.settings.pages.manage-home-feed-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Homepage feed ordering';

	protected static ?string $title = 'Homepage feed ordering';

	protected static ?string $slug = 'homepage-feed-ordering';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Newest-first + recent shuffle')
					->description('New uploads are prioritized (newest first), but items within a recent window are shuffled deterministically using a seed for variety and scroll stability.')
					->schema([
						TextInput::make('recent_window_days')
							->label('Recent window (days)')
							->numeric()
							->minValue(HomeFeedSettingsService::MIN_RECENT_WINDOW_DAYS)
							->maxValue(HomeFeedSettingsService::MAX_RECENT_WINDOW_DAYS)
							->default(7)
							->required(),

						TextInput::make('recent_shuffle_pool_limit')
							->label('Recent shuffle pool limit')
							->numeric()
							->minValue(HomeFeedSettingsService::MIN_RECENT_POOL_LIMIT)
							->maxValue(HomeFeedSettingsService::MAX_RECENT_POOL_LIMIT)
							->default(300)
							->helperText('Upper bound for how many recent items participate in shuffle. Larger values increase variety but cost more.')
							->required(),

						TextInput::make('seed_window_minutes')
							->label('Seed stability window (minutes)')
							->numeric()
							->minValue(HomeFeedSettingsService::MIN_SEED_WINDOW_MINUTES)
							->maxValue(HomeFeedSettingsService::MAX_SEED_WINDOW_MINUTES)
							->default(60)
							->helperText('Within this time window, the same seed yields a stable ordering to avoid reshuffles while scrolling.')
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
				['key_slug' => 'home_feed'],
				[
					'key_name' => 'Homepage feed ordering',
					'key_slug' => 'home_feed',
					'value' => 'configured',
					'description' => 'Organic homepage ordering settings (newest-first + recent shuffle)',
					'data' => $data,
				]
			);

			Notification::make()
				->title('Homepage feed settings saved')
				->success()
				->send();
		} catch (\Throwable $e) {
			\Log::error('Homepage feed settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving homepage feed settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'home_feed')->first();
		$saved = ($row && is_array($row->data)) ? $row->data : [];

		return array_merge([
			'recent_window_days' => (int) ($saved['recent_window_days'] ?? 7),
			'recent_shuffle_pool_limit' => (int) ($saved['recent_shuffle_pool_limit'] ?? 300),
			'seed_window_minutes' => (int) ($saved['seed_window_minutes'] ?? 60),
		], $saved);
	}
}

