<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
	private const BASE_URL = 'https://api.paystack.co';

	public function initializeTransaction(array $payload): array
	{
		$response = Http::withToken(PaystackSettingsService::getSecretKey())
			->acceptJson()
			->post(self::BASE_URL . '/transaction/initialize', $payload);

		$response->throw();

		$decoded = $response->json();

		return is_array($decoded) ? $decoded : [];
	}

	public function verifyTransaction(string $reference): array
	{
		$response = Http::withToken(PaystackSettingsService::getSecretKey())
			->acceptJson()
			->get(self::BASE_URL . '/transaction/verify/' . urlencode($reference));

		$response->throw();

		$decoded = $response->json();

		return is_array($decoded) ? $decoded : [];
	}

	public function generateReference(string $prefix = 'gag'): string
	{
		return $prefix . '_' . Str::ulid();
	}

	public function computeWebhookSignature(string $payload, string $secret): string
	{
		return hash_hmac('sha512', $payload, $secret);
	}

	public function getWebhookSecret(): string
	{
		return PaystackSettingsService::getWebhookSecret();
	}
}

