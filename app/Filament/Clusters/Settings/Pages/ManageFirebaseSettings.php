<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Models\Setting;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Filament\Clusters\Settings;

class ManageFirebaseSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-bell';

	protected static string $view = 'filament.clusters.settings.pages.manage-firebase-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Firebase Settings';

	protected static ?string $title = 'Firebase Settings';

	protected static ?string $slug = 'firebase-settings';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Firebase Configuration')
					->description('Configure Firebase for push notifications and other services')
					->schema([
						TextInput::make('firebase_project_id')
							->label('Project ID')
							->required()
							->helperText('Your Firebase project ID')
							->placeholder('your-project-id'),

						TextInput::make('firebase_api_key')
							->label('Web API Key')
							->required()
							->helperText('Your Firebase Web API Key'),

						TextInput::make('firebase_sender_id')
							->label('Sender ID')
							->required()
							->helperText('Your Firebase Sender ID for push notifications'),

						TextInput::make('firebase_app_id')
							->label('App ID')
							->required()
							->helperText('Your Firebase App ID'),

						Textarea::make('firebase_service_account_key')
							->label('Service Account Key (JSON)')
							->required()
							->rows(8)
							->helperText('Paste your Firebase service account JSON key here')
							->placeholder('{"type": "service_account", "project_id": "...", ...}'),

						TextInput::make('firebase_server_key')
							->label('Server Key')
							->required()
							->password()
							->helperText('Your Firebase Server Key for FCM'),

						Select::make('firebase_environment')
							->label('Environment')
							->options([
								'development' => 'Development',
								'staging' => 'Staging',
								'production' => 'Production',
							])
							->default('development')
							->required()
							->helperText('Select your Firebase environment'),

						Toggle::make('firebase_enabled')
							->label('Enable Firebase')
							->default(true)
							->helperText('Enable or disable Firebase services'),

						Toggle::make('push_notifications_enabled')
							->label('Enable Push Notifications')
							->default(true)
							->helperText('Enable or disable push notifications'),

						Toggle::make('analytics_enabled')
							->label('Enable Analytics')
							->default(true)
							->helperText('Enable or disable Firebase Analytics'),

						Toggle::make('crashlytics_enabled')
							->label('Enable Crashlytics')
							->default(true)
							->helperText('Enable or disable Firebase Crashlytics'),

						TextInput::make('firebase_topic')
							->label('Default Topic')
							->helperText('Default topic for push notifications (optional)')
							->placeholder('general'),

						Textarea::make('firebase_custom_claims')
							->label('Custom Claims (JSON)')
							->rows(4)
							->helperText('Custom claims for Firebase Auth (optional JSON)')
							->placeholder('{"role": "user", "permissions": ["read", "write"]}'),
					])
					->columns(2),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			// Get form data
			$data = $this->form->getState();

			// Debug: Log the data
			\Log::info('Firebase Settings Save - Form Data:', $data);

			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->body('Form data is empty')
					->danger()
					->send();
				return;
			}

			// Save as single Firebase record with JSON data
			$setting = Setting::updateOrCreate(
				['key_slug' => 'firebase'],
				[
					'key_name' => 'Firebase Configuration',
					'key_slug' => 'firebase',
					'value' => 'configured',
					'description' => 'Firebase services configuration',
					'data' => $data,
				]
			);

			// Debug: Log saved setting
			\Log::info('Firebase Settings Save - Saved as single record:', ['key' => 'firebase', 'data' => $data]);

			Notification::make()
				->title("Firebase settings saved successfully")
				->body("All Firebase configuration saved as single record")
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('Firebase Settings Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving Firebase settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		// Get Firebase settings from single record
		$firebaseSetting = Setting::where('key_slug', 'firebase')->first();

		$firebaseData = [];
		if ($firebaseSetting && $firebaseSetting->data) {
			$firebaseData = $firebaseSetting->data;
		}

		// Set default values for empty fields
		$defaults = [
			'firebase_environment' => 'development',
			'firebase_enabled' => 'true',
			'push_notifications_enabled' => 'true',
			'analytics_enabled' => 'true',
			'crashlytics_enabled' => 'true',
		];

		return array_merge($defaults, $firebaseData);
	}
}
