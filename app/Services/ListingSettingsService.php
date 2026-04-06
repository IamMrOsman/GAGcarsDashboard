<?php

namespace App\Services;

use App\Models\Setting;

class ListingSettingsService
{
	public const MIN_DAYS = 1;

	public const MAX_DAYS = 365;

	/**
	 * Days a listing stays active after becoming Active (from admin Settings, then config/env).
	 */
	public static function getActiveListingDays(): int
	{
		$setting = Setting::where('key_slug', 'listing')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$raw = $data['listing_active_days'] ?? null;

		if ($raw !== null && $raw !== '') {
			$days = (int) $raw;
			if ($days >= self::MIN_DAYS && $days <= self::MAX_DAYS) {
				return $days;
			}
		}

		$fallback = (int) config('gagcars.listing_active_days', 30);
		if ($fallback < self::MIN_DAYS) {
			return self::MIN_DAYS;
		}
		if ($fallback > self::MAX_DAYS) {
			return self::MAX_DAYS;
		}

		return $fallback;
	}
}
