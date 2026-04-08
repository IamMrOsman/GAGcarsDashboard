<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Models\Setting;
use Filament\Forms\Get;
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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use App\Filament\Clusters\Settings;
use App\Support\EventMessageCatalog;

class ManageEventMessages extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';

	protected static string $view = 'filament.clusters.settings.pages.manage-event-messages';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Event Messages';

	protected static ?string $title = 'Event Messages';

	protected static ?string $slug = 'event-messages';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('System Event Messages')
					->description('Templates are keyed by event; see placeholder hints below each message. OTP flows use hardcoded SMS/email only when no enabled template covers that channel.')
					->schema([
						Repeater::make('event_messages')
							->label('Event Messages')
							->schema([
								Grid::make(2)
									->schema([
										Select::make('event')
											->label('Event')
											->options(EventMessageCatalog::LABELS)
											->required()
											->searchable()
											->live()
											->helperText('Select the system event; labels match code triggers in EventMessageCatalog'),

										Select::make('channel')
											->label('Channel')
											->options([
												'email' => 'Email',
												'sms' => 'SMS',
												'both' => 'Both Email & SMS',
											])
											->required()
											->default('email')
											->helperText('Select the communication channel'),
									]),

								Textarea::make('message')
									->label('Message Template')
									->required()
									->rows(4)
									->helperText(function (Get $get): string {
										$ev = $get('event');
										$extra = is_string($ev) ? EventMessageCatalog::hintFor($ev) : '';
										$base = 'Common: {user_name}, {email}, {phone}, {otp}, {item_name}, {amount}. ';

										return $extra !== '' ? $base.'Suggested for this event: '.$extra : $base.'Select an event above for suggested placeholders.';
									})
									->placeholder('Hello {user_name}, your {item_name} has been sold for {amount}. Thank you!'),

								Toggle::make('enabled')
									->label('Enable Message')
									->default(true)
									->helperText('Enable or disable this event message'),
							])
							->columns(1)
							->collapsible()
							->itemLabel(fn (array $state): ?string => isset($state['event'])
								? (EventMessageCatalog::LABELS[$state['event']] ?? $state['event'])
								: null)
							->addActionLabel('Add Event Message')
							->defaultItems(0),
					]),
			])
			->statePath('data');
	}

	public function save(): void
	{
		try {
			// Get form data
			$data = $this->form->getState();

			if (empty($data)) {
				Notification::make()
					->title('No data to save')
					->body('Form data is empty')
					->danger()
					->send();
				return;
			}

			// Save as single Event Messages record with JSON data
			$setting = Setting::updateOrCreate(
				['key_slug' => 'event_messages'],
				[
					'key_name' => 'Event Messages Configuration',
					'key_slug' => 'event_messages',
					'value' => 'configured',
					'description' => 'System event message templates configuration',
					'data' => $data,
				]
			);

			Notification::make()
				->title("Event messages saved successfully")
				->body("All event message templates saved successfully")
				->success()
				->send();
		} catch (\Exception $e) {
			\Log::error('Event Messages Save Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

			Notification::make()
				->title('Error saving event messages')
				->body($e->getMessage())
				->danger()
				->send();
		}
	}

	private function getFormData(): array
	{
		// Get Event Messages settings from single record
		$eventMessagesSetting = Setting::where('key_slug', 'event_messages')->first();

		$eventMessagesData = [];
		if ($eventMessagesSetting && $eventMessagesSetting->data) {
			$eventMessagesData = $eventMessagesSetting->data;
		}

		// Set default values for empty fields
		$defaults = [
			'event_messages' => [
				[
					'event' => 'new_account',
					'channel' => 'email',
					'message' => 'Welcome {user_name}! Your account has been created successfully.',
					'enabled' => true,
				],
				[
					'event' => 'item_sold',
					'channel' => 'both',
					'message' => 'Congratulations {user_name}! Your {item_name} has been sold for {amount}.',
					'enabled' => true,
				],
				[
					'event' => 'payment_successful',
					'channel' => 'email',
					'message' => 'Payment of {amount} has been processed successfully. Thank you!',
					'enabled' => true,
				],
			],
		];

		return array_merge($defaults, $eventMessagesData);
	}
}
