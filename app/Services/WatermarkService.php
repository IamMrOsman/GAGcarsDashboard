<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WatermarkService
{
	public static function isEnabled(): bool
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$raw = $data['watermark_enabled'] ?? false;

		return filter_var($raw, FILTER_VALIDATE_BOOL);
	}

	/**
	 * Apply a tiled watermark to an existing image on disk (public disk path).
	 *
	 * @param string $publicDiskPath e.g. "items/abc.jpg"
	 */
	public static function applyToPublicPath(string $publicDiskPath): void
	{
		if (!self::isEnabled()) {
			return;
		}

		$abs = Storage::disk('public')->path($publicDiskPath);
		self::applyToAbsolutePath($abs);
	}

	/**
	 * Apply watermark in-place. Uses GD (no extra composer packages).
	 */
	public static function applyToAbsolutePath(string $absolutePath): void
	{
		try {
			$config = self::getConfig();
			if (!$config['enabled']) {
				return;
			}

			$base = self::loadImage($absolutePath);
			if (!$base) {
				return;
			}

			$wm = self::buildWatermarkTile($config);
			if (!$wm) {
				imagedestroy($base);
				return;
			}

			$bw = imagesx($base);
			$bh = imagesy($base);
			$ww = imagesx($wm);
			$wh = imagesy($wm);
			$gap = (int) $config['tile_gap'];

			imagealphablending($base, true);
			imagesavealpha($base, true);

			// Tile across the image.
			for ($y = -$wh; $y < $bh + $wh; $y += ($wh + $gap)) {
				for ($x = -$ww; $x < $bw + $ww; $x += ($ww + $gap)) {
					imagecopy($base, $wm, $x, $y, 0, 0, $ww, $wh);
				}
			}

			self::saveImage($base, $absolutePath);

			imagedestroy($wm);
			imagedestroy($base);
		} catch (\Throwable $e) {
			Log::error('Watermark failed', [
				'path' => $absolutePath,
				'error' => $e->getMessage(),
			]);
		}
	}

	/**
	 * Download a remote image URL, watermark it, store to public disk, and return the stored path.
	 * Returns null on failure.
	 */
	public static function watermarkRemoteUrlToPublic(string $url, string $directory = 'items'): ?string
	{
		if (!self::isEnabled()) {
			// If disabled, keep remote URL (do not download).
			return $url;
		}

		try {
			$res = Http::timeout(25)
				->withHeaders([
					// Prefer formats we can process with GD.
					'Accept' => 'image/jpeg,image/png,image/webp;q=0.9,*/*;q=0.1',
				])
				->get($url);
			if (!$res->successful()) {
				return null;
			}

			$contentType = (string) $res->header('Content-Type');
			$extFromType = self::guessExtension($contentType);
			if ($extFromType === null) {
				// Cloudinary (or another CDN) might negotiate AVIF; we can't process that with GD.
				// In that case, keep the original remote URL so the app can still display it.
				return $url;
			}

			$bytes = $res->body();
			if ($bytes === '') {
				return null;
			}

			$ext = $extFromType ?? self::guessExtensionFromUrl($url) ?? 'jpg';
			$name = Str::lower((string) Str::ulid()) . '.' . $ext;
			$path = trim($directory, '/') . '/' . $name;

			Storage::disk('public')->put($path, $bytes);

			$abs = Storage::disk('public')->path($path);
			// If we can't load/process, fall back to remote URL to avoid breaking image display.
			if (self::loadImage($abs)) {
				self::applyToAbsolutePath($abs);
			} else {
				return $url;
			}

			return $path;
		} catch (\Throwable $e) {
			Log::error('Watermark remote URL failed', ['url' => $url, 'error' => $e->getMessage()]);
			return null;
		}
	}

	private static function guessExtension(?string $contentType): ?string
	{
		if (!$contentType) return null;
		$contentType = strtolower(trim(explode(';', $contentType)[0]));
		return match ($contentType) {
			'image/jpeg', 'image/jpg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			default => null,
		};
	}

	private static function guessExtensionFromUrl(string $url): ?string
	{
		$path = parse_url($url, PHP_URL_PATH);
		if (!is_string($path) || $path === '') return null;
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : null;
	}

	private static function getConfig(): array
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];

		$enabled = filter_var($data['watermark_enabled'] ?? false, FILTER_VALIDATE_BOOL);
		$logoPath = (string) ($data['watermark_logo_path'] ?? '');
		$text = trim((string) ($data['watermark_text'] ?? ''));

		$opacity = (int) ($data['watermark_opacity_percent'] ?? 18);
		$opacity = max(1, min(100, $opacity));

		$rotation = (int) ($data['watermark_rotation_deg'] ?? 30);
		$rotation = max(-85, min(85, $rotation));

		$scale = (int) ($data['watermark_scale_percent'] ?? 22);
		$scale = max(5, min(80, $scale));

		$gap = (int) ($data['watermark_tile_gap_px'] ?? 120);
		$gap = max(0, min(800, $gap));

		return [
			'enabled' => $enabled,
			'logo_path' => $logoPath,
			'text' => $text,
			'opacity_percent' => $opacity,
			'rotation_deg' => $rotation,
			'scale_percent' => $scale,
			'tile_gap' => $gap,
		];
	}

	private static function buildWatermarkTile(array $config): mixed
	{
		$logoDiskPath = $config['logo_path'];
		$logoAbs = $logoDiskPath !== '' ? Storage::disk('public')->path($logoDiskPath) : null;

		$logo = $logoAbs ? self::loadImage($logoAbs) : null;
		$text = $config['text'];

		if (!$logo && $text === '') {
			return null;
		}

		// Determine tile size based on logo or text.
		$tileW = 300;
		$tileH = 180;
		if ($logo) {
			$tileW = max($tileW, imagesx($logo));
			$tileH = max($tileH, imagesy($logo));
		}
		if ($text !== '') {
			$tileH += 30;
		}

		$tile = imagecreatetruecolor($tileW, $tileH);
		imagealphablending($tile, false);
		imagesavealpha($tile, true);
		$transparent = imagecolorallocatealpha($tile, 0, 0, 0, 127);
		imagefilledrectangle($tile, 0, 0, $tileW, $tileH, $transparent);

		// Draw logo centered.
		if ($logo) {
			imagealphablending($logo, true);
			imagesavealpha($logo, true);

			$scaled = self::scaleImageToPercent($logo, (int) $config['scale_percent']);
			$lw = imagesx($scaled);
			$lh = imagesy($scaled);
			$dx = (int) (($tileW - $lw) / 2);
			$dy = (int) (($tileH - $lh) / 2) - ($text !== '' ? 10 : 0);
			imagecopy($tile, $scaled, $dx, $dy, 0, 0, $lw, $lh);
			imagedestroy($scaled);
			imagedestroy($logo);
		}

		// Draw text below (GD built-in font).
		if ($text !== '') {
			$color = imagecolorallocatealpha($tile, 255, 255, 255, 0);
			$font = 3; // built-in font
			$tw = imagefontwidth($font) * strlen($text);
			$tx = (int) (($tileW - $tw) / 2);
			$ty = $tileH - (imagefontheight($font) + 10);
			imagestring($tile, $font, max(0, $tx), max(0, $ty), $text, $color);
		}

		// Rotate tile
		$angle = (int) $config['rotation_deg'];
		if ($angle !== 0) {
			$rot = imagerotate($tile, $angle, $transparent);
			imagedestroy($tile);
			$tile = $rot;
			imagesavealpha($tile, true);
		}

		// Apply opacity by adjusting alpha channel.
		self::applyOpacity($tile, (int) $config['opacity_percent']);

		return $tile;
	}

	private static function applyOpacity($im, int $opacityPercent): void
	{
		$opacityPercent = max(1, min(100, $opacityPercent));
		// Convert to additional alpha (0..127); higher opacity => lower alpha.
		$targetAlpha = (int) round(127 * (1 - ($opacityPercent / 100)));

		$w = imagesx($im);
		$h = imagesy($im);
		imagealphablending($im, false);
		imagesavealpha($im, true);

		for ($y = 0; $y < $h; $y++) {
			for ($x = 0; $x < $w; $x++) {
				$rgba = imagecolorat($im, $x, $y);
				$a = ($rgba & 0x7F000000) >> 24;
				// Only adjust non-fully-transparent pixels.
				if ($a < 127) {
					$newAlpha = max($a, $targetAlpha);
					$r = ($rgba >> 16) & 0xFF;
					$g = ($rgba >> 8) & 0xFF;
					$b = $rgba & 0xFF;
					$col = imagecolorallocatealpha($im, $r, $g, $b, $newAlpha);
					imagesetpixel($im, $x, $y, $col);
				}
			}
		}
	}

	private static function scaleImageToPercent($im, int $percent): mixed
	{
		$percent = max(1, min(100, $percent));
		$w = imagesx($im);
		$h = imagesy($im);
		$nw = max(1, (int) round($w * ($percent / 100)));
		$nh = max(1, (int) round($h * ($percent / 100)));

		$out = imagecreatetruecolor($nw, $nh);
		imagealphablending($out, false);
		imagesavealpha($out, true);
		$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
		imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
		imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);

		return $out;
	}

	private static function loadImage(string $absolutePath): mixed
	{
		if (!is_file($absolutePath)) {
			return null;
		}

		$info = @getimagesize($absolutePath);
		if (!$info) {
			return null;
		}

		return match ($info[2]) {
			IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
			IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
			IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
			default => null,
		};
	}

	private static function saveImage($im, string $absolutePath): void
	{
		$info = @getimagesize($absolutePath);
		if (!$info) {
			return;
		}

		match ($info[2]) {
			IMAGETYPE_JPEG => imagejpeg($im, $absolutePath, 88),
			IMAGETYPE_PNG => imagepng($im, $absolutePath, 6),
			IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($im, $absolutePath, 85) : null,
			default => null,
		};
	}
}

