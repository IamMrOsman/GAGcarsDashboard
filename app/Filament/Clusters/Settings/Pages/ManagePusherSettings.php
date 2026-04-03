<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Models\Setting;
use Filament\Forms\Get;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\Clusters\Settings;

class ManagePusherSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-signal';

	protected static string $view = 'filament.clusters.settings.pages.manage-pusher-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Pusher & broadcast';

	protected static ?string $title = 'Pusher & broadcast settings';

	protected static ?string $slug = 'pusher-settings';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Pusher / Laravel broadcasting')
					->description('Realtime chat (Chatify) and broadcasting use these credentials. When enabled, values saved here override PUSHER_* entries in .env.')
					->schema([
						Toggle::make('pusher_enabled')
							->label('Use dashboard Pusher config')
							->default(false)
							->helperText('When on, the fields below override .env for this application. When off, only .env is used.'),

						TextInput::make('pusher_app_id')
							->label('Pusher App ID')
							->required(fn (Get $get): bool => (bool) $get('pusher_enabled'))
							->helperText('Same as PUSHER_APP_ID'),

						TextInput::make('pusher_app_key')
							->label('Pusher Key')
							->required(fn (Get $get): bool => (bool) $get('pusher_enabled'))
							->helperText('Public key; also exposed to the mobile app via the client-config API.'),

						TextInput::make('pusher_app_secret')
							->label('Pusher Secret')
							->password()
							->revealable()
							->required(fn (Get $get): bool => (bool) $get('pusher_enabled'))
							->helperText('Server-only; never sent to the app.'),

						TextInput::make('pusher_app_cluster')
							->label('Cluster')
							->default('mt1')
							->helperText('e.g. mt1, eu, ap1'),

						TextInput::make('pusher_host')
							->label('Host (optional)')
							->helperText('Leave empty for Pusher-hosted (cluster is used). Set for self-hosted or custom endpoints.'),

						TextInput::make('pusher_port')
							->label('Port')
							->numeric()
							->default(443)
							->helperText('Usually 443 for HTTPS.'),

						Select::make('pusher_scheme')
							->label('Scheme')
							->options([
								'https' => 'https',
								'http' => 'http',
							])
							->default('https')
							->required(),

						Select::make('broadcast_connection')
							->label('Default broadcast connection')
							->options([
								'pusher' => 'Pusher',
								'log' => 'Log (debug)',
								'null' => 'Null (disabled)',
							])
							->default('pusher')
							->required()
							->helperText('Laravel BROADCAST_CONNECTION equivalent.'),
					])
					->columns(2),
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
					->body('Form data is empty')
					->danger()
					->send();

				return;
			}

			Setting::updateOrCreate(
				['key_slug' => 'pusher'],
				[
					'key_name' => 'Pusher & broadcast',
					'key_slug' => 'pusher',
					'value' => 'configured',
					'description' => 'Pusher and Laravel broadcasting configuration',
					'data' => $data,
				]
			);

			Notification::make()
				->title('Pusher settings saved')
				->body('Restart queue workers if you use broadcasting in queues. Config is applied on the next request.')
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('Pusher Settings Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving Pusher settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'pusher')->first();
		$saved = ($row && $row->data) ? $row->data : [];

		$defaults = [
			'pusher_enabled' => false,
			'pusher_app_cluster' => 'mt1',
			'pusher_port' => 443,
			'pusher_scheme' => 'https',
			'broadcast_connection' => 'pusher',
		];

		return array_merge($defaults, $saved);
	}
}
