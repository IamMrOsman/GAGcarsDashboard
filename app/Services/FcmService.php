<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
	/**
	 * Sends a push notification using legacy FCM HTTP API.
	 *
	 * Env required:
	 * - FCM_SERVER_KEY
	 *
	 * Note: This is intentionally simple to get you working quickly.
	 * If/when you want HTTP v1, we can switch to a service account-based sender.
	 */
	public function sendToTokens(array $tokens, array $payload): void
	{
		$tokens = array_values(array_filter(array_unique(array_map('trim', $tokens))));
		if (count($tokens) === 0) return;

		$serverKey = env('FCM_SERVER_KEY');
		if (!$serverKey) {
			Log::warning('FCM_SERVER_KEY missing; skipping push');
			return;
		}

		$body = array_merge($payload, [
			'registration_ids' => $tokens,
		]);

		try {
			$response = Http::withHeaders([
				'Authorization' => 'key=' . $serverKey,
				'Content-Type' => 'application/json',
			])->post('https://fcm.googleapis.com/fcm/send', $body);

			if (!$response->successful()) {
				Log::error('FCM push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM push exception', ['error' => $e->getMessage()]);
		}
	}

	public function sendToTopic(string $topic, array $payload): void
	{
		$serverKey = env('FCM_SERVER_KEY');
		if (!$serverKey) {
			Log::warning('FCM_SERVER_KEY missing; skipping push');
			return;
		}

		$body = array_merge($payload, [
			'to' => '/topics/' . $topic,
		]);

		try {
			$response = Http::withHeaders([
				'Authorization' => 'key=' . $serverKey,
				'Content-Type' => 'application/json',
			])->post('https://fcm.googleapis.com/fcm/send', $body);

			if (!$response->successful()) {
				Log::error('FCM topic push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM topic push exception', ['error' => $e->getMessage()]);
		}
	}
}

