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
use App\Services\SmtpSettingsService;

class ManageSmtpSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-envelope';

	protected static string $view = 'filament.clusters.settings.pages.manage-smtp-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'SMTP Settings';

	protected static ?string $title = 'SMTP Settings';

	protected static ?string $slug = 'smtp-settings';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('SMTP Configuration')
					->description('Configure your email server settings')
					->schema([
						TextInput::make('smtp_host')
							->label('SMTP Host')
							->required()
							->placeholder('smtp.gmail.com')
							->helperText('The hostname of your SMTP server'),

						TextInput::make('smtp_port')
							->label('SMTP Port')
							->required()
							->numeric()
							->minValue(1)
							->maxValue(65535)
							->default('587')
							->helperText('The port number for SMTP (usually 587 for TLS or 465 for SSL)'),

						Select::make('smtp_encryption')
							->label('Encryption')
							->options([
								'tls' => 'TLS',
								'ssl' => 'SSL',
								'none' => 'None',
							])
							->default('tls')
							->required()
							->helperText('The encryption method to use'),

						TextInput::make('smtp_username')
							->label('Username')
							->required()
							->helperText('Your SMTP username or email address'),

						TextInput::make('smtp_password')
							->label('Password')
							->password()
							->required()
							->helperText('Your SMTP password or app password'),

						TextInput::make('smtp_from_address')
							->label('From Address')
							->email()
							->required()
							->rules(['email'])
							->helperText('The email address that emails will be sent from'),

						TextInput::make('smtp_from_name')
							->label('From Name')
							->required()
							->helperText('The name that will appear as the sender'),

						Toggle::make('smtp_enabled')
							->label('Enable SMTP')
							->default(true)
							->helperText('Enable or disable SMTP email sending'),
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
			\Log::info('SMTP Settings Save - Form Data:', $data);

			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->body('Form data is empty')
					->danger()
					->send();
				return;
			}

			// Save as single SMTP record with JSON data
			$setting = Setting::updateOrCreate(
				['key_slug' => 'smtp'],
				[
					'key_name' => 'SMTP Configuration',
					'key_slug' => 'smtp',
					'value' => 'configured',
					'description' => 'SMTP email server configuration',
					'data' => $data,
				]
			);

			// Debug: Log saved setting
			\Log::info('SMTP Settings Save - Saved as single record:', ['key' => 'smtp', 'data' => $data]);

			Notification::make()
				->title("SMTP settings saved successfully")
				->body("All SMTP configuration saved as single record")
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('SMTP Settings Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving SMTP settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}



	private function getFormData(): array
	{
		// Get SMTP settings from single record
		$smtpSetting = Setting::where('key_slug', 'smtp')->first();

		$smtpData = [];
		if ($smtpSetting && $smtpSetting->data) {
			$smtpData = $smtpSetting->data;
		}

		// Set default values for empty fields
		$defaults = [
			'smtp_port' => '587',
			'smtp_encryption' => 'tls',
			'smtp_enabled' => 'true',
		];

		return array_merge($defaults, $smtpData);
	}
}
