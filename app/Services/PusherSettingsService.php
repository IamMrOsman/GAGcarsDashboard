<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class PusherSettingsService
{
	/**
	 * Normalized Pusher config from DB when enabled and required fields are set; otherwise [] (use .env / files only).
	 *
	 * @return array<string, mixed>
	 */
	public static function getConfig(): array
	{
		try {
			$row = Setting::where('key_slug', 'pusher')->first();
			if (!$row || !$row->data) {
				return [];
			}
			$d = $row->data;
			if (!($d['pusher_enabled'] ?? false)) {
				return [];
			}
			$key = trim((string) ($d['pusher_app_key'] ?? ''));
			$secret = trim((string) ($d['pusher_app_secret'] ?? ''));
			$appId = trim((string) ($d['pusher_app_id'] ?? ''));
			if ($key === '' || $secret === '' || $appId === '') {
				return [];
			}
			$cluster = trim((string) ($d['pusher_app_cluster'] ?? 'mt1')) ?: 'mt1';
			$scheme = trim((string) ($d['pusher_scheme'] ?? 'https')) ?: 'https';
			$port = isset($d['pusher_port']) && $d['pusher_port'] !== '' && $d['pusher_port'] !== null
				? (int) $d['pusher_port']
				: (int) env('PUSHER_PORT', 443);
			if ($port <= 0) {
				$port = 443;
			}
			$host = trim((string) ($d['pusher_host'] ?? ''));
			$broadcastDefault = $d['broadcast_connection'] ?? 'pusher';
			if (!in_array($broadcastDefault, ['pusher', 'log', 'null'], true)) {
				$broadcastDefault = 'pusher';
			}

			return [
				'key' => $key,
				'secret' => $secret,
				'app_id' => $appId,
				'cluster' => $cluster,
				'scheme' => $scheme,
				'port' => $port,
				'host' => $host,
				'broadcast_default' => $broadcastDefault,
			];
		} catch (\Throwable $e) {
			return [];
		}
	}

	public static function applyToConfig(): void
	{
		try {
			$c = self::getConfig();
			if ($c === []) {
				return;
			}

			$useTls = $c['scheme'] === 'https';
			$host = $c['host'];

			config([
				'broadcasting.default' => $c['broadcast_default'],
				'broadcasting.connections.pusher' => [
					'driver' => 'pusher',
					'key' => $c['key'],
					'secret' => $c['secret'],
					'app_id' => $c['app_id'],
					'options' => [
						'cluster' => $c['cluster'],
						'useTLS' => $useTls,
						'host' => $host !== '' ? $host : null,
						'port' => $c['port'],
						'scheme' => $c['scheme'],
					],
				],
			]);

			$chatifyHost = $host !== '' ? $host : ('api-'.$c['cluster'].'.pusher.com');
			$debug = config('chatify.pusher.debug', config('app.debug', false));

			config([
				'chatify.pusher' => [
					'debug' => $debug,
					'key' => $c['key'],
					'secret' => $c['secret'],
					'app_id' => $c['app_id'],
					'options' => [
						'cluster' => $c['cluster'],
						'host' => $chatifyHost,
						'port' => $c['port'],
						'scheme' => $c['scheme'],
						'encrypted' => true,
						'useTLS' => $useTls,
					],
				],
			]);
		} catch (\Throwable $e) {
			Log::warning('PusherSettingsService::applyToConfig failed', ['error' => $e->getMessage()]);
		}
	}

	/**
	 * Client-safe values for mobile apps (no secret).
	 *
	 * @return array{pusher_key: string, pusher_cluster: string, pusher_scheme: string, broadcast_connection: string}
	 */
	public static function getPublicClientConfig(): array
	{
		$c = self::getConfig();
		if ($c !== []) {
			return [
				'pusher_key' => $c['key'],
				'pusher_cluster' => $c['cluster'],
				'pusher_scheme' => $c['scheme'],
				'broadcast_connection' => $c['broadcast_default'],
			];
		}

		return [
			'pusher_key' => (string) env('PUSHER_APP_KEY', ''),
			'pusher_cluster' => (string) env('PUSHER_APP_CLUSTER', 'mt1'),
			'pusher_scheme' => (string) env('PUSHER_SCHEME', 'https'),
			'broadcast_connection' => (string) env('BROADCAST_CONNECTION', 'pusher'),
		];
	}
}
