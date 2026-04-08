<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageAppMaintenance extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

	protected static string $view = 'filament.clusters.settings.pages.manage-app-maintenance';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Maintenance mode';

	protected static ?string $title = 'Maintenance mode';

	protected static ?string $slug = 'maintenance-mode';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Under maintenance')
					->description('When enabled, the mobile app is blocked for everyone and API calls return 503 (except login and client-config).')
					->schema([
						Toggle::make('maintenance_enabled')
							->label('Enable maintenance mode')
							->default(false),
					]),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			$data = $this->form->getState();
			$row = Setting::where('key_slug', 'app')->first();
			$existing = ($row && is_array($row->data)) ? $row->data : [];
			$merged = array_merge($existing, $data);

			Setting::updateOrCreate(
				['key_slug' => 'app'],
				[
					'key_name' => 'App settings',
					'key_slug' => 'app',
					'value' => 'configured',
					'description' => 'Mobile client configuration and operational toggles',
					'data' => $merged,
				]
			);

			Notification::make()
				->title('Maintenance settings saved')
				->success()
				->send();
		} catch (\Throwable $e) {
			\Log::error('Maintenance settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'app')->first();
		$saved = ($row && is_array($row->data)) ? $row->data : [];

		return array_merge([
			'maintenance_enabled' => (bool) ($saved['maintenance_enabled'] ?? false),
		], $saved);
	}
}

