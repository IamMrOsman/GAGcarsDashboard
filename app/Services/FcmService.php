<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
	/**
	 * Sends to device registration tokens.
	 * Prefers FCM HTTP v1 (service account) when configured; falls back to legacy API + server key.
	 */
	public function sendToTokens(array $tokens, array $payload): void
	{
		$tokens = array_values(array_filter(array_unique(array_map('trim', $tokens))));
		if (count($tokens) === 0) {
			return;
		}

		if (! self::pushAllowedBySettings()) {
			Log::info('FCM send skipped (push notifications disabled in settings)');
			return;
		}

		if (self::canSendHttpV1()) {
			foreach ($tokens as $deviceToken) {
				self::sendHttpV1ToToken($deviceToken, $payload);
			}
			return;
		}

		self::sendToTokensLegacy($tokens, $payload);
	}

	/**
	 * Sends to an FCM topic (e.g. blog, broadcasts).
	 */
	public function sendToTopic(string $topic, array $payload): void
	{
		$topic = trim($topic);
		if ($topic === '') {
			return;
		}

		if (! self::pushAllowedBySettings()) {
			Log::info('FCM topic send skipped (push notifications disabled in settings)');
			return;
		}

		if (self::canSendHttpV1()) {
			self::sendHttpV1ToTopic($topic, $payload);
			return;
		}

		self::sendToTopicLegacy($topic, $payload);
	}

	private static function pushAllowedBySettings(): bool
	{
		$data = self::firebaseData();
		if ($data === null) {
			return true;
		}
		$enabled = $data['firebase_enabled'] ?? true;
		$push = $data['push_notifications_enabled'] ?? true;
		if ($enabled === false || $enabled === 'false' || $enabled === 0) {
			return false;
		}
		if ($push === false || $push === 'false' || $push === 0) {
			return false;
		}
		return true;
	}

	/** @return array<string, mixed>|null */
	private static function firebaseData(): ?array
	{
		try {
			$row = Setting::where('key_slug', 'firebase')->first();
			$d = $row?->data;
			return is_array($d) ? $d : null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	private static function canSendHttpV1(): bool
	{
		$projectId = self::resolveProjectId();
		$sa = self::resolveServiceAccount();
		return $projectId !== null && $projectId !== '' && $sa !== null;
	}

	private static function resolveProjectId(): ?string
	{
		$data = self::firebaseData();
		$fromForm = is_array($data) ? ($data['firebase_project_id'] ?? null) : null;
		if (is_string($fromForm) && trim($fromForm) !== '') {
			return trim($fromForm);
		}
		$sa = self::resolveServiceAccount();
		if (is_array($sa) && ! empty($sa['project_id'])) {
			return (string) $sa['project_id'];
		}
		return null;
	}

	/** @return array<string, mixed>|null */
	private static function resolveServiceAccount(): ?array
	{
		$data = self::firebaseData();
		if (! is_array($data)) {
			return null;
		}
		$raw = $data['firebase_service_account_key'] ?? null;
		if (! is_string($raw) || trim($raw) === '') {
			return null;
		}
		try {
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
			if (! is_array($decoded) || empty($decoded['private_key']) || empty($decoded['client_email'])) {
				return null;
			}
			return $decoded;
		} catch (\Throwable $e) {
			Log::warning('FCM: invalid service account JSON in settings', ['error' => $e->getMessage()]);
			return null;
		}
	}

	private static function fetchOAuthAccessToken(array $serviceAccount): ?string
	{
		$email = (string) ($serviceAccount['client_email'] ?? '');
		$cacheKey = 'fcm_oauth_v1_' . hash('sha256', $email);
		$cached = Cache::get($cacheKey);
		if (is_string($cached) && $cached !== '') {
			return $cached;
		}

		$jwt = self::createServiceAccountJwt($serviceAccount);
		if ($jwt === null) {
			return null;
		}

		try {
			$response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion' => $jwt,
			]);
			if (! $response->successful()) {
				Log::error('FCM OAuth token exchange failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
				return null;
			}
			$json = $response->json();
			$access = $json['access_token'] ?? null;
			if (! is_string($access) || $access === '') {
				return null;
			}
			Cache::put($cacheKey, $access, now()->addMinutes(50));
			return $access;
		} catch (\Throwable $e) {
			Log::error('FCM OAuth exception', ['error' => $e->getMessage()]);
			return null;
		}
	}

	private static function createServiceAccountJwt(array $serviceAccount): ?string
	{
		$clientEmail = (string) ($serviceAccount['client_email'] ?? '');
		$privateKeyPem = (string) ($serviceAccount['private_key'] ?? '');
		if ($clientEmail === '' || $privateKeyPem === '') {
			return null;
		}
		$privateKeyPem = str_replace('\\n', "\n", $privateKeyPem);

		$now = time();
		$header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
		$claim = self::base64UrlEncode(json_encode([
			'iss' => $clientEmail,
			'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
			'aud' => 'https://oauth2.googleapis.com/token',
			'iat' => $now,
			'exp' => $now + 3600,
		], JSON_UNESCAPED_SLASHES));

		$unsigned = $header . '.' . $claim;

		$key = openssl_pkey_get_private($privateKeyPem);
		if ($key === false) {
			Log::error('FCM: could not parse service account private key');
			return null;
		}
		$signature = '';
		$ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
		unset($key);
		if (! $ok) {
			Log::error('FCM: openssl_sign failed for service account JWT');
			return null;
		}

		return $unsigned . '.' . self::base64UrlEncode($signature);
	}

	private static function base64UrlEncode(string $data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * @param  array<string, mixed>  $payload
	 */
	private static function sendHttpV1ToToken(string $deviceToken, array $payload): void
	{
		$projectId = self::resolveProjectId();
		$sa = self::resolveServiceAccount();
		if ($projectId === null || $sa === null) {
			return;
		}
		$access = self::fetchOAuthAccessToken($sa);
		if ($access === null) {
			Log::warning('FCM HTTP v1: no OAuth token; skipping token send');
			return;
		}

		$message = self::buildV1MessageForDevice($deviceToken, $payload);
		$url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

		try {
			$response = Http::withToken($access)
				->acceptJson()
				->post($url, ['message' => $message]);

			if (! $response->successful()) {
				Log::error('FCM HTTP v1 token push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM HTTP v1 token push exception', ['error' => $e->getMessage()]);
		}
	}

	/**
	 * @param  array<string, mixed>  $payload
	 */
	private static function sendHttpV1ToTopic(string $topic, array $payload): void
	{
		$projectId = self::resolveProjectId();
		$sa = self::resolveServiceAccount();
		if ($projectId === null || $sa === null) {
			return;
		}
		$access = self::fetchOAuthAccessToken($sa);
		if ($access === null) {
			Log::warning('FCM HTTP v1: no OAuth token; skipping topic send');
			return;
		}

		$message = self::buildV1MessageForTopic($topic, $payload);
		$url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

		try {
			$response = Http::withToken($access)
				->acceptJson()
				->post($url, ['message' => $message]);

			if (! $response->successful()) {
				Log::error('FCM HTTP v1 topic push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM HTTP v1 topic push exception', ['error' => $e->getMessage()]);
		}
	}

	/**
	 * @param  array<string, mixed>  $payload
	 * @return array<string, mixed>
	 */
	private static function buildV1MessageForDevice(string $token, array $payload): array
	{
		$m = [
			'token' => $token,
		];
		self::applyLegacyPayloadToV1Message($m, $payload);
		return $m;
	}

	/**
	 * @param  array<string, mixed>  $payload
	 * @return array<string, mixed>
	 */
	private static function buildV1MessageForTopic(string $topic, array $payload): array
	{
		$m = [
			'topic' => $topic,
		];
		self::applyLegacyPayloadToV1Message($m, $payload);
		return $m;
	}

	/**
	 * Maps our legacy FCM body shape into HTTP v1 `message` fields.
	 *
	 * @param  array<string, mixed>  $message  (passed by ref semantics via reassignment)
	 * @param  array<string, mixed>  $payload
	 */
	private static function applyLegacyPayloadToV1Message(array &$message, array $payload): void
	{
		$priority = isset($payload['priority']) ? strtolower(trim((string) $payload['priority'])) : null;

		if (isset($payload['notification']) && is_array($payload['notification'])) {
			$n = $payload['notification'];
			$message['notification'] = [
				'title' => (string) ($n['title'] ?? ''),
				'body' => (string) ($n['body'] ?? ''),
			];
		}
		if (isset($payload['data']) && is_array($payload['data'])) {
			$data = [];
			foreach ($payload['data'] as $k => $v) {
				$data[(string) $k] = is_scalar($v) || $v === null
					? (string) $v
					: json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			$message['data'] = $data;
		}
		if (isset($payload['android']) && is_array($payload['android'])) {
			$message['android'] = $payload['android'];
		}
		if (isset($payload['apns']) && is_array($payload['apns'])) {
			$message['apns'] = $payload['apns'];
		}

		// Ensure "high priority" behaves consistently in HTTP v1.
		// Legacy API accepts top-level `priority`, but HTTP v1 expects platform-specific fields.
		if ($priority === 'high') {
			if (! isset($message['android']) || ! is_array($message['android'])) {
				$message['android'] = [];
			}
			if (empty($message['android']['priority'])) {
				$message['android']['priority'] = 'HIGH';
			}

			// For notification alerts on iOS, include APNs headers so delivery is treated as an alert.
			// (Push capability + APNs setup still required in Apple/Firebase.)
			if (! isset($message['apns']) || ! is_array($message['apns'])) {
				$message['apns'] = [];
			}
			if (! isset($message['apns']['headers']) || ! is_array($message['apns']['headers'])) {
				$message['apns']['headers'] = [];
			}
			$message['apns']['headers']['apns-priority'] = (string) ($message['apns']['headers']['apns-priority'] ?? '10');
			$message['apns']['headers']['apns-push-type'] = (string) ($message['apns']['headers']['apns-push-type'] ?? 'alert');
		}
	}

	/**
	 * @param  array<string, mixed>  $payload
	 */
	private static function sendToTokensLegacy(array $tokens, array $payload): void
	{
		$serverKey = self::resolveServerKey();
		if (! $serverKey) {
			Log::warning('FCM server key missing (FCM_SERVER_KEY or Firebase settings); skipping push');
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

			if (! $response->successful()) {
				Log::error('FCM legacy push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM legacy push exception', ['error' => $e->getMessage()]);
		}
	}

	/**
	 * @param  array<string, mixed>  $payload
	 */
	private static function sendToTopicLegacy(string $topic, array $payload): void
	{
		$serverKey = self::resolveServerKey();
		if (! $serverKey) {
			Log::warning('FCM server key missing (FCM_SERVER_KEY or Firebase settings); skipping push');
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

			if (! $response->successful()) {
				Log::error('FCM legacy topic push failed', [
					'status' => $response->status(),
					'body' => $response->body(),
				]);
			}
		} catch (\Throwable $e) {
			Log::error('FCM legacy topic push exception', ['error' => $e->getMessage()]);
		}
	}

	private static function resolveServerKey(): ?string
	{
		$fromEnv = env('FCM_SERVER_KEY');
		if (is_string($fromEnv) && trim($fromEnv) !== '') {
			return trim($fromEnv);
		}

		try {
			$row = Setting::where('key_slug', 'firebase')->first();
			$key = $row?->data['firebase_server_key'] ?? null;
			if (is_string($key) && trim($key) !== '') {
				return trim($key);
			}
		} catch (\Throwable $e) {
			// ignore
		}

		return null;
	}
}
