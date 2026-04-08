<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudinaryUploadService
{
	public function __construct(private readonly CloudinarySignService $signer)
	{
	}

	/**
	 * Upload a local image file to Cloudinary.
	 *
	 * @return array{public_id: string, secure_url: string}
	 */
	public function uploadLocalImage(string $absolutePath, string $folder = 'watermarks', ?string $publicId = null): array
	{
		$cloudName = $this->signer->cloudName();
		$apiKey = $this->signer->apiKey();
		if ($cloudName === '' || $apiKey === '') {
			throw new \RuntimeException('Cloudinary is not configured on the server.');
		}

		$publicId = $publicId && trim($publicId) !== '' ? trim($publicId) : ($folder . '/' . Str::lower((string) Str::ulid()));
		$timestamp = time();

		$paramsToSign = [
			'folder' => $folder,
			'public_id' => $publicId,
			'timestamp' => $timestamp,
			'overwrite' => 1,
			'invalidate' => 1,
		];
		$signature = $this->signer->sign($paramsToSign);
		if ($signature === '') {
			throw new \RuntimeException('Cloudinary signing is not configured on the server.');
		}

		$endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

		$res = Http::asMultipart()
			->timeout(60)
			->attach('file', file_get_contents($absolutePath), basename($absolutePath))
			->post($endpoint, [
				'api_key' => $apiKey,
				'timestamp' => $timestamp,
				'folder' => $folder,
				'public_id' => $publicId,
				'overwrite' => 1,
				'invalidate' => 1,
				'signature' => $signature,
			]);

		if (!$res->successful()) {
			throw new \RuntimeException('Cloudinary upload failed: ' . $res->body());
		}

		$json = $res->json();
		$outPublicId = is_array($json) ? (string)($json['public_id'] ?? '') : '';
		$secureUrl = is_array($json) ? (string)($json['secure_url'] ?? '') : '';

		if ($outPublicId === '' || $secureUrl === '') {
			throw new \RuntimeException('Cloudinary upload succeeded but response was missing public_id/secure_url.');
		}

		return [
			'public_id' => $outPublicId,
			'secure_url' => $secureUrl,
		];
	}
}

