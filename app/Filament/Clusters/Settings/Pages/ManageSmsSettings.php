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
use App\Filament\Clusters\Settings;

class ManageSmsSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

	protected static string $view = 'filament.clusters.settings.pages.manage-sms-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'SMS Settings';

	protected static ?string $title = 'SMS Settings';

	protected static ?string $slug = 'sms-settings';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('SMS Configuration')
					->description('Configure your SMS service settings')
					->schema([
						Select::make('sms_provider')
							->label('SMS Provider')
							->options([
								'arkesel' => 'Arkesel',
								'twilio' => 'Twilio',
								'africastalking' => 'Africa\'s Talking',
								'custom' => 'Custom Provider',
							])
							->default('arkesel')
							->required()
							->live()
							->helperText('Select your SMS service provider'),

						TextInput::make('sms_api_key')
							->label('API Key')
							->required()
							->helperText('Your SMS service API key')
							->visible(fn (Get $get) => in_array($get('sms_provider'), ['arkesel', 'twilio', 'africastalking', 'custom'])),

						TextInput::make('sms_sender_id')
							->label('Sender ID')
							->required()
							->helperText('The sender ID that will appear on SMS messages')
							->visible(fn (Get $get) => in_array($get('sms_provider'), ['arkesel', 'africastalking', 'custom'])),

						TextInput::make('sms_username')
							->label('Username')
							->helperText('Username for your SMS service account')
							->visible(fn (Get $get) => in_array($get('sms_provider'), ['africastalking', 'custom'])),

						TextInput::make('sms_password')
							->label('Password')
							->password()
							->helperText('Password for your SMS service account')
							->visible(fn (Get $get) => in_array($get('sms_provider'), ['africastalking', 'custom'])),

						TextInput::make('sms_account_sid')
							->label('Account SID')
							->helperText('Your Twilio Account SID')
							->visible(fn (Get $get) => $get('sms_provider') === 'twilio'),

						TextInput::make('sms_auth_token')
							->label('Auth Token')
							->password()
							->helperText('Your Twilio Auth Token')
							->visible(fn (Get $get) => $get('sms_provider') === 'twilio'),

						TextInput::make('sms_phone_number')
							->label('Phone Number')
							->helperText('Your Twilio phone number (e.g., +1234567890)')
							->visible(fn (Get $get) => $get('sms_provider') === 'twilio'),

						TextInput::make('sms_api_url')
							->label('API URL')
							->url()
							->helperText('Custom SMS service API endpoint')
							->visible(fn (Get $get) => $get('sms_provider') === 'custom'),

						Toggle::make('sms_enabled')
							->label('Enable SMS')
							->default(true)
							->helperText('Enable or disable SMS sending'),

						Toggle::make('sms_test_mode')
							->label('Test Mode')
							->default(false)
							->helperText('Enable test mode to prevent actual SMS sending'),
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
			\Log::info('SMS Settings Save - Form Data:', $data);

			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->body('Form data is empty')
					->danger()
					->send();
				return;
			}

			// Save as single SMS record with JSON data
			$setting = Setting::updateOrCreate(
				['key_slug' => 'sms'],
				[
					'key_name' => 'SMS Configuration',
					'key_slug' => 'sms',
					'value' => 'configured',
					'description' => 'SMS service configuration',
					'data' => $data,
				]
			);

			// Debug: Log saved setting
			\Log::info('SMS Settings Save - Saved as single record:', ['key' => 'sms', 'data' => $data]);

			Notification::make()
				->title("SMS settings saved successfully")
				->body("All SMS configuration saved as single record")
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('SMS Settings Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving SMS settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		// Get SMS settings from single record
		$smsSetting = Setting::where('key_slug', 'sms')->first();

		$smsData = [];
		if ($smsSetting && $smsSetting->data) {
			$smsData = $smsSetting->data;
		}

		// Set default values for empty fields
		$defaults = [
			'sms_provider' => 'arkesel',
			'sms_enabled' => 'true',
			'sms_test_mode' => 'false',
		];

		return array_merge($defaults, $smsData);
	}
}
