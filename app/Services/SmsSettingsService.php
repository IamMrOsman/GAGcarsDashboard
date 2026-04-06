<?php

namespace App\Services;

use App\Models\Setting;

class SmsSettingsService
{
	/**
	 * @return array<string, mixed>
	 */
	public static function getSmsData(): array
	{
		$s = Setting::where('key_slug', 'sms')->first();

		return ($s && is_array($s->data)) ? $s->data : [];
	}

	public static function truthy(mixed $value): bool
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_string($value)) {
			return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
		}

		return (bool) $value;
	}

	/**
	 * SMS is allowed to be sent (not globally disabled and not in test mode).
	 */
	public static function isSmsSendingEnabled(): bool
	{
		$d = self::getSmsData();
		if ($d === []) {
			return true;
		}

		return self::truthy($d['sms_enabled'] ?? true) && ! self::truthy($d['sms_test_mode'] ?? false);
	}

	/**
	 * Arkesel can send when enabled, not in test mode, and required fields present.
	 */
	public static function isArkeselReady(): bool
	{
		if (! self::isSmsSendingEnabled()) {
			return false;
		}

		$d = self::getSmsData();
		$provider = (string) ($d['sms_provider'] ?? 'arkesel');
		if ($provider !== '' && $provider !== 'arkesel') {
			return false;
		}

		$key = self::getArkeselApiKey();
		$sender = self::getArkeselSenderId();

		return $key !== null && $key !== '' && $sender !== null && $sender !== '';
	}

	public static function getArkeselApiKey(): ?string
	{
		$d = self::getSmsData();
		$key = $d['sms_api_key'] ?? null;

		return is_string($key) && $key !== '' ? $key : null;
	}

	public static function getArkeselSenderId(): ?string
	{
		$d = self::getSmsData();
		$sender = $d['sms_sender_id'] ?? null;

		return is_string($sender) && $sender !== '' ? $sender : null;
	}
}
