<?php

namespace App\Services;

use App\Models\Setting;

class AppMaintenanceService
{
	/**
	 * Reads maintenance mode flag from Settings (key_slug = "app").
	 */
	public static function isEnabled(): bool
	{
		$setting = Setting::where('key_slug', 'app')->first();
		$data = $setting && is_array($setting->data) ? $setting->data : [];
		$raw = $data['maintenance_enabled'] ?? null;

		return filter_var($raw, FILTER_VALIDATE_BOOL);
	}
}

