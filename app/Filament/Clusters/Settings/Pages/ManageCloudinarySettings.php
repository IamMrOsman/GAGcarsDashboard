<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;

class ManageCloudinarySettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-photo';

	protected static string $view = 'filament.clusters.settings.pages.manage-cloudinary-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Cloudinary';

	protected static ?string $title = 'Cloudinary';

	protected static ?string $slug = 'cloudinary';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Cloudinary credentials')
					->description('Used for backend-signed uploads. Keep these credentials off the mobile app.')
					->schema([
						TextInput::make('cloudinary_cloud_name')
							->label('Cloud name')
							->required()
							->maxLength(100),

						TextInput::make('cloudinary_api_key')
							->label('API key')
							->required()
							->maxLength(120),

						TextInput::make('cloudinary_api_secret')
							->label('API secret')
							->password()
							->revealable()
							->required()
							->maxLength(200)
							->helperText('This is stored encrypted in the database.'),
					])
					->columns(2),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			$data = $this->form->getState();
			$cloud = trim((string)($data['cloudinary_cloud_name'] ?? ''));
			$key = trim((string)($data['cloudinary_api_key'] ?? ''));
			$secret = trim((string)($data['cloudinary_api_secret'] ?? ''));

			if ($cloud === '' || $key === '' || $secret === '') {
				Notification::make()
					->title('Please fill all Cloudinary fields')
					->danger()
					->send();
				return;
			}

			$row = Setting::where('key_slug', 'app')->first();
			$saved = ($row && is_array($row->data)) ? $row->data : [];

			$saved['cloudinary_cloud_name'] = $cloud;
			$saved['cloudinary_api_key'] = $key;
			$saved['cloudinary_api_secret_enc'] = Crypt::encryptString($secret);

			Setting::updateOrCreate(
				['key_slug' => 'app'],
				[
					'key_name' => 'App settings',
					'key_slug' => 'app',
					'value' => 'configured',
					'description' => 'Mobile client configuration and operational toggles',
					'data' => $saved,
				]
			);

			Notification::make()
				->title('Cloudinary settings saved')
				->success()
				->send();
		} catch (\Throwable $e) {
			\Log::error('Cloudinary settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving Cloudinary settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		$row = Setting::where('key_slug', 'app')->first();
		$saved = ($row && is_array($row->data)) ? $row->data : [];

		$cloud = (string)($saved['cloudinary_cloud_name'] ?? env('CLOUDINARY_CLOUD_NAME', ''));
		$key = (string)($saved['cloudinary_api_key'] ?? env('CLOUDINARY_API_KEY', ''));

		$secret = '';
		$enc = $saved['cloudinary_api_secret_enc'] ?? null;
		if (is_string($enc) && $enc !== '') {
			try {
				$secret = Crypt::decryptString($enc);
			} catch (\Throwable) {
				$secret = '';
			}
		} else {
			$secret = (string) env('CLOUDINARY_API_SECRET', '');
		}

		return array_merge([
			'cloudinary_cloud_name' => $cloud,
			'cloudinary_api_key' => $key,
			'cloudinary_api_secret' => $secret,
		], $saved);
	}
}

