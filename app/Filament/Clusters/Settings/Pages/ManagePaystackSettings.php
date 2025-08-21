<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Models\Setting;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Filament\Clusters\Settings;

class ManagePaystackSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-credit-card';

	protected static string $view = 'filament.clusters.settings.pages.manage-paystack-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Paystack API';

	protected static ?string $title = 'Paystack API Settings';

	protected static ?string $slug = 'paystack-settings';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Paystack API Configuration')
					->description('Configure your Paystack payment gateway settings')
					->schema([
						TextInput::make('paystack_live_secret_key')
							->label('Live Secret Key')
							->required()
							->password()
							->helperText('Your Paystack live secret key for production transactions'),

						TextInput::make('paystack_live_public_key')
							->label('Live Public Key')
							->required()
							->helperText('Your Paystack live public key for frontend integration'),

						TextInput::make('paystack_test_secret_key')
							->label('Test Secret Key')
							->password()
							->helperText('Your Paystack test secret key for development/testing'),

						TextInput::make('paystack_test_public_key')
							->label('Test Public Key')
							->helperText('Your Paystack test public key for development/testing'),

						Toggle::make('paystack_live_mode')
							->label('Live Mode')
							->default(false)
							->helperText('Enable live mode for production transactions. Disable for test mode.'),

						Toggle::make('paystack_enabled')
							->label('Enable Paystack')
							->default(true)
							->helperText('Enable or disable Paystack payment processing'),
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
			\Log::info('Paystack Settings Save - Form Data:', $data);

			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->body('Form data is empty')
					->danger()
					->send();
				return;
			}

			// Save as single Paystack record with JSON data
			$setting = Setting::updateOrCreate(
				['key_slug' => 'paystack'],
				[
					'key_name' => 'Paystack API Configuration',
					'key_slug' => 'paystack',
					'value' => 'configured',
					'description' => 'Paystack payment gateway configuration',
					'data' => $data,
				]
			);

			// Debug: Log saved setting
			\Log::info('Paystack Settings Save - Saved as single record:', ['key' => 'paystack', 'data' => $data]);

			Notification::make()
				->title("Paystack settings saved successfully")
				->body("All Paystack configuration saved as single record")
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('Paystack Settings Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving Paystack settings')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		// Get Paystack settings from single record
		$paystackSetting = Setting::where('key_slug', 'paystack')->first();

		$paystackData = [];
		if ($paystackSetting && $paystackSetting->data) {
			$paystackData = $paystackSetting->data;
		}

		// Set default values for empty fields
		$defaults = [
			'paystack_live_mode' => false,
			'paystack_enabled' => true,
		];

		return array_merge($defaults, $paystackData);
	}
}
