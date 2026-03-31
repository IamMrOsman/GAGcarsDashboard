<?php

namespace App\Services;

use App\Models\Setting;

class PaystackSettingsService
{
	public static function getPaystackConfig(): array
	{
		$paystackSetting = Setting::where('key_slug', 'paystack')->first();

		if (!$paystackSetting || !$paystackSetting->data) {
			// Fallback to config file
			return config('services.paystack', []);
		}

		$paystackSettings = $paystackSetting->data;

		// Check if Paystack is enabled
		if (($paystackSettings['paystack_enabled'] ?? false) !== true) {
			return [];
		}

		// Return Paystack config format
		return [
			'live_secret_key' => $paystackSettings['paystack_live_secret_key'] ?? '',
			'live_public_key' => $paystackSettings['paystack_live_public_key'] ?? '',
			'test_secret_key' => $paystackSettings['paystack_test_secret_key'] ?? '',
			'test_public_key' => $paystackSettings['paystack_test_public_key'] ?? '',
			'webhook_secret' => $paystackSettings['paystack_webhook_secret'] ?? '',
			'webhook_url' => $paystackSettings['paystack_webhook_url'] ?? '',
			'callback_url' => $paystackSettings['paystack_callback_url'] ?? '',
			'live_mode' => $paystackSettings['paystack_live_mode'] ?? false,
			'enabled' => $paystackSettings['paystack_enabled'] ?? false,
		];
	}

	public static function isPaystackConfigured(): bool
	{
		$paystackSetting = Setting::where('key_slug', 'paystack')->first();

		if (!$paystackSetting || !$paystackSetting->data) {
			return false;
		}

		$paystackSettings = $paystackSetting->data;

		// Check if Paystack is enabled
		if (($paystackSettings['paystack_enabled'] ?? false) !== true) {
			return false;
		}

		// Check if live mode is enabled and live keys are present
		if (($paystackSettings['paystack_live_mode'] ?? false) === true) {
			$requiredFields = ['paystack_live_secret_key', 'paystack_live_public_key'];
		} else {
			$requiredFields = ['paystack_test_secret_key', 'paystack_test_public_key'];
		}

		foreach ($requiredFields as $field) {
			if (empty($paystackSettings[$field])) {
				return false;
			}
		}

		return true;
	}

	public static function getSecretKey(): string
	{
		$config = self::getPaystackConfig();

		if (($config['live_mode'] ?? false) === true) {
			return $config['live_secret_key'] ?? '';
		}

		return $config['test_secret_key'] ?? '';
	}

	public static function getPublicKey(): string
	{
		$config = self::getPaystackConfig();

		if (($config['live_mode'] ?? false) === true) {
			return $config['live_public_key'] ?? '';
		}

		return $config['test_public_key'] ?? '';
	}

	public static function isLiveMode(): bool
	{
		$config = self::getPaystackConfig();
		return ($config['live_mode'] ?? false) === true;
	}

	public static function getWebhookSecret(): string
	{
		$config = self::getPaystackConfig();

		if (!empty($config['webhook_secret'])) {
			return (string) $config['webhook_secret'];
		}

		return self::getSecretKey();
	}

	public static function getPublicConfig(): array
	{
		$config = self::getPaystackConfig();

		return [
			'enabled' => (bool) ($config['enabled'] ?? false),
			'live_mode' => (bool) ($config['live_mode'] ?? false),
			'public_key' => self::getPublicKey(),
			'webhook_url' => (string) ($config['webhook_url'] ?? ''),
			'callback_url' => (string) ($config['callback_url'] ?? ''),
		];
	}

	public static function getSetting(string $key, $default = null)
	{
		// For Paystack settings, get from the single record
		if (str_starts_with($key, 'paystack_')) {
			$paystackSetting = Setting::where('key_slug', 'paystack')->first();
			if ($paystackSetting && $paystackSetting->data) {
				return $paystackSetting->data[$key] ?? $default;
			}
			return $default;
		}

		// For other settings, use the original method
		$setting = Setting::where('key_slug', $key)->first();
		return $setting ? $setting->value : $default;
	}
}
