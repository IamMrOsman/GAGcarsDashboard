<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class CloudinarySignService
{
	public function cloudName(): string
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$val = trim((string)($data['cloudinary_cloud_name'] ?? ''));

		return $val !== '' ? $val : (string) config('services.cloudinary.cloud_name', env('CLOUDINARY_CLOUD_NAME', ''));
	}

	public function apiKey(): string
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$val = trim((string)($data['cloudinary_api_key'] ?? ''));

		return $val !== '' ? $val : (string) config('services.cloudinary.api_key', env('CLOUDINARY_API_KEY', ''));
	}

	private function apiSecret(): string
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$enc = $data['cloudinary_api_secret_enc'] ?? null;

		if (is_string($enc) && $enc !== '') {
			try {
				$secret = Crypt::decryptString($enc);
				$secret = trim((string) $secret);
				if ($secret !== '') {
					return $secret;
				}
			} catch (\Throwable) {
				// fall through to env/config
			}
		}

		return (string) config('services.cloudinary.api_secret', env('CLOUDINARY_API_SECRET', ''));
	}

	/**
	 * Builds a watermark transformation suitable for upload API `transformation` param.
	 * Uses a Cloudinary public_id overlay (logo) centered, with opacity and relative scaling.
	 */
	public function buildCenteredWatermarkTransformation(): ?string
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];

		$enabled = filter_var($data['watermark_enabled'] ?? false, FILTER_VALIDATE_BOOL);
		if (!$enabled) {
			return null;
		}

		$publicId = trim((string)($data['watermark_cloudinary_public_id'] ?? env('CLOUDINARY_WATERMARK_PUBLIC_ID', '')));
		if ($publicId === '') {
			return null;
		}

		$opacity = (int)($data['watermark_opacity_percent'] ?? 18);
		$opacity = max(1, min(100, $opacity));

		$scalePercent = (int)($data['watermark_scale_percent'] ?? 22);
		$scalePercent = max(5, min(80, $scalePercent));
		$relativeWidth = max(0.05, min(0.80, $scalePercent / 100));

		// Cloudinary overlay public_id encoding: slashes become colons.
		$overlayId = str_replace('/', ':', $publicId);

		// Example: l_watermarks:gag_logo,g_center,o_18,fl_relative,w_0.22
		return sprintf(
			'l_%s,g_center,o_%d,fl_relative,w_%.2f',
			$overlayId,
			$opacity,
			$relativeWidth
		);
	}

	/**
	 * Cloudinary signature algorithm: sha1(sortedParamsString + api_secret).
	 *
	 * @param array<string, scalar> $params
	 */
	public function sign(array $params): string
	{
		$secret = $this->apiSecret();
		if ($secret === '') {
			return '';
		}

		// Remove empty/null and non-scalars; Cloudinary signs simple params.
		$filtered = [];
		foreach ($params as $k => $v) {
			if ($v === null) continue;
			if (is_string($v) && trim($v) === '') continue;
			if (!is_scalar($v)) continue;
			$filtered[$k] = $v;
		}

		ksort($filtered);

		$pairs = [];
		foreach ($filtered as $k => $v) {
			$pairs[] = $k . '=' . $v;
		}

		$toSign = implode('&', $pairs);

		return sha1($toSign . $secret);
	}
}

