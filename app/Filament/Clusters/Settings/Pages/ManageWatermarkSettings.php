<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Setting;
use App\Services\CloudinaryUploadService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ManageWatermarkSettings extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $navigationIcon = 'heroicon-o-swatch';

	protected static string $view = 'filament.clusters.settings.pages.manage-watermark-settings';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'Watermark';

	protected static ?string $title = 'Watermark';

	protected static ?string $slug = 'watermark';

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill($this->getFormData());
	}

	protected function getHeaderActions(): array
	{
		return [
			Action::make('uploadWatermarkToCloudinary')
				->label('Upload watermark to Cloudinary')
				->modalHeading('Upload watermark logo to Cloudinary')
				->modalSubmitActionLabel('Upload')
				->form([
					FileUpload::make('logo')
						->label('Watermark logo (PNG)')
						->image()
						->disk('public')
						->directory('watermarks/tmp')
						->preserveFilenames(false)
						->required(),
					TextInput::make('public_id')
						->label('Optional Cloudinary public id')
						->placeholder('e.g. watermarks/gag_logo')
						->helperText('Leave empty to auto-generate a unique public_id.'),
				])
				->action(function (array $data, CloudinaryUploadService $uploader): void {
					$tmpPath = (string) ($data['logo'] ?? '');
					if ($tmpPath === '') {
						Notification::make()->title('Please select a file')->danger()->send();
						return;
					}

					$abs = Storage::disk('public')->path($tmpPath);
					$result = $uploader->uploadLocalImage(
						$abs,
						'watermarks',
						trim((string) ($data['public_id'] ?? '')) ?: null
					);

					// Persist Cloudinary public_id into app settings.
					$row = Setting::where('key_slug', 'app')->first();
					$saved = ($row && is_array($row->data)) ? $row->data : [];
					$saved['watermark_cloudinary_public_id'] = $result['public_id'];

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

					// Update the UI state so the user sees it immediately.
					$this->form->fill($this->getFormData());

					// Cleanup temp file.
					Storage::disk('public')->delete($tmpPath);

					Notification::make()
						->title('Uploaded to Cloudinary')
						->body('Saved public id: ' . $result['public_id'])
						->success()
						->send();
				}),
		];
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Listing image watermark')
					->description('Applies to newly uploaded item listing images. Recommended: a transparent PNG + optional short text.')
					->schema([
						Toggle::make('watermark_enabled')
							->label('Enable watermark')
							->default(false),

						FileUpload::make('watermark_logo_path')
							->label('Watermark logo (PNG)')
							->image()
							->disk('public')
							->directory('watermarks')
							->preserveFilenames()
							->helperText('Upload a transparent PNG. This will be tiled/rotated across the image.'),

						TextInput::make('watermark_text')
							->label('Optional watermark text')
							->placeholder('e.g. GAGCARS')
							->maxLength(60),

						TextInput::make('watermark_cloudinary_public_id')
							->label('Cloudinary watermark public id')
							->placeholder('e.g. watermarks/gag_logo')
							->helperText('Upload your watermark logo to Cloudinary once, then put its public_id here. This is required for backend-enforced Cloudinary watermarking.'),

						TextInput::make('watermark_opacity_percent')
							->label('Opacity (%)')
							->numeric()
							->minValue(1)
							->maxValue(100)
							->default(18)
							->helperText('Lower values are more subtle.'),

						TextInput::make('watermark_rotation_deg')
							->label('Rotation (degrees)')
							->numeric()
							->minValue(-85)
							->maxValue(85)
							->default(30),

						TextInput::make('watermark_scale_percent')
							->label('Logo scale (%)')
							->numeric()
							->minValue(5)
							->maxValue(80)
							->default(22),

						TextInput::make('watermark_tile_gap_px')
							->label('Tile gap (px)')
							->numeric()
							->minValue(0)
							->maxValue(800)
							->default(120),
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
				['key_slug' => 'app'],
				[
					'key_name' => 'App settings',
					'key_slug' => 'app',
					'value' => 'configured',
					'description' => 'Mobile client configuration and operational toggles',
					'data' => $data,
				]
			);

			Notification::make()
				->title('Watermark settings saved')
				->success()
				->send();
		} catch (\Throwable $e) {
			\Log::error('Watermark settings save error', ['error' => $e->getMessage()]);
			Notification::make()
				->title('Error saving watermark settings')
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
			'watermark_enabled' => (bool) ($saved['watermark_enabled'] ?? false),
			'watermark_logo_path' => (string) ($saved['watermark_logo_path'] ?? ''),
			'watermark_text' => (string) ($saved['watermark_text'] ?? ''),
			'watermark_cloudinary_public_id' => (string) ($saved['watermark_cloudinary_public_id'] ?? ''),
			'watermark_opacity_percent' => (int) ($saved['watermark_opacity_percent'] ?? 18),
			'watermark_rotation_deg' => (int) ($saved['watermark_rotation_deg'] ?? 30),
			'watermark_scale_percent' => (int) ($saved['watermark_scale_percent'] ?? 22),
			'watermark_tile_gap_px' => (int) ($saved['watermark_tile_gap_px'] ?? 120),
		], $saved);
	}
}

