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
		$transformation = $svc->buildCenteredWatermarkTransformation();
		$watermarkEnabled = WatermarkService::isEnabled();
		if ($watermarkEnabled && $transformation === null) {
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
				'transformation' => $transformation ?? '',
				'signature' => $signature,
			],
		]);
	}
}

