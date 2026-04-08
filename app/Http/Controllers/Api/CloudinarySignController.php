<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinarySignService;
use App\Services\WatermarkService;
use Illuminate\Http\Request;

class CloudinarySignController extends Controller
{
	public function __invoke(Request $request, CloudinarySignService $svc)
	{
		// Only allow known upload folders from the mobile app.
		$allowedFolders = [
			'vehicle_uploads', // item listing images
			'profile_images',
			'chat_uploads',
			'promotions',
			'verifications',
			'payment_uploads',
		];

		$folder = (string) $request->input('folder', 'vehicle_uploads');
		if (!in_array($folder, $allowedFolders, true)) {
			return response()->json([
				'message' => 'Invalid upload folder.',
			], 422);
		}

		$cloudName = $svc->cloudName();
		$apiKey = $svc->apiKey();
		if ($cloudName === '' || $apiKey === '') {
			return response()->json([
				'message' => 'Cloudinary is not configured on the server.',
			], 500);
		}

		$timestamp = time();
		$kind = strtolower((string) $request->input('kind', 'listing'));

		$transformation = match ($kind) {
			// Enforced listing watermark on upload.
			'listing' => $svc->buildCenteredWatermarkTransformation(),
			// Profile image optimization (no watermark).
			'profile' => 'w_500,h_500,c_fill,g_face,q_auto',
			// Vehicle image optimization without watermark (used by some flows).
			'vehicle' => 'w_800,h_600,c_fill,q_auto',
			// No transform; just server-signed upload.
			default => null,
		};
		$watermarkEnabled = WatermarkService::isEnabled();
		if ($kind === 'listing' && $watermarkEnabled && $transformation === null) {
			return response()->json([
				'message' => 'Watermark is enabled but Cloudinary watermark public id is not configured.',
				'error_code' => 'watermark_cloudinary_public_id_missing',
			], 422);
		}

		$paramsToSign = [
			'folder' => $folder,
			'timestamp' => $timestamp,
		];
		if (is_string($transformation) && $transformation !== '') {
			$paramsToSign['transformation'] = $transformation;
		}

		$signature = $svc->sign($paramsToSign);
		if ($signature === '') {
			return response()->json([
				'message' => 'Cloudinary signing is not configured on the server.',
			], 500);
		}

		return response()->json([
			'data' => [
				'cloud_name' => $cloudName,
				'api_key' => $apiKey,
				'timestamp' => $timestamp,
				'folder' => $folder,
				'kind' => $kind,
				'transformation' => $transformation ?? '',
				'signature' => $signature,
			],
		]);
	}
}

