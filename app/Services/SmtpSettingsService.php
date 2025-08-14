<?php

namespace App\Services;

use App\Models\Setting;

class SmtpSettingsService
{
	public static function getSmtpConfig(): array
	{
		$smtpSetting = Setting::where('key_slug', 'smtp')->first();

		if (!$smtpSetting || !$smtpSetting->data) {
			return [];
		}

		$smtpSettings = $smtpSetting->data;

		// Check if SMTP is enabled
		if (($smtpSettings['smtp_enabled'] ?? 'false') !== 'true') {
			return [];
		}

		// Return Laravel mail config format
		return [
			'mailers' => [
				'smtp' => [
					'transport' => 'smtp',
					'host' => $smtpSettings['smtp_host'] ?? '',
					'port' => $smtpSettings['smtp_port'] ?? '587',
					'encryption' => $smtpSettings['smtp_encryption'] ?? 'tls',
					'username' => $smtpSettings['smtp_username'] ?? '',
					'password' => $smtpSettings['smtp_password'] ?? '',
					'timeout' => null,
					'local_domain' => env('MAIL_EHLO_DOMAIN'),
				],
			],
			'from' => [
				'address' => $smtpSettings['smtp_from_address'] ?? '',
				'name' => $smtpSettings['smtp_from_name'] ?? '',
			],
		];
	}

	public static function isSmtpConfigured(): bool
	{
		$smtpSetting = Setting::where('key_slug', 'smtp')->first();

		if (!$smtpSetting || !$smtpSetting->data) {
			return false;
		}

		$smtpSettings = $smtpSetting->data;
		$requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'];

		foreach ($requiredFields as $field) {
			if (empty($smtpSettings[$field])) {
				return false;
			}
		}

		return ($smtpSettings['smtp_enabled'] ?? 'false') === 'true';
	}

	public static function getSetting(string $key, $default = null)
	{
		// For SMTP settings, get from the single record
		if (str_starts_with($key, 'smtp_')) {
			$smtpSetting = Setting::where('key_slug', 'smtp')->first();
			if ($smtpSetting && $smtpSetting->data) {
				return $smtpSetting->data[$key] ?? $default;
			}
			return $default;
		}

		// For other settings, use the original method
		$setting = Setting::where('key_slug', $key)->first();
		return $setting ? $setting->value : $default;
	}
}
