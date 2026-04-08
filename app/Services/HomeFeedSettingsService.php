<?php

namespace App\Services;

use App\Models\Setting;

class HomeFeedSettingsService
{
	public const MIN_RECENT_WINDOW_DAYS = 1;
	public const MAX_RECENT_WINDOW_DAYS = 60;

	public const MIN_RECENT_POOL_LIMIT = 50;
	public const MAX_RECENT_POOL_LIMIT = 2000;

	public const MIN_SEED_WINDOW_MINUTES = 1;
	public const MAX_SEED_WINDOW_MINUTES = 24 * 60;

	public static function get(): array
	{
		$row = Setting::where('key_slug', 'home_feed')->first();
		$data = ($row && is_array($row->data)) ? $row->data : [];

		$recentWindowDays = (int) ($data['recent_window_days'] ?? 7);
		$recentWindowDays = max(self::MIN_RECENT_WINDOW_DAYS, min(self::MAX_RECENT_WINDOW_DAYS, $recentWindowDays));

		$poolLimit = (int) ($data['recent_shuffle_pool_limit'] ?? 300);
		$poolLimit = max(self::MIN_RECENT_POOL_LIMIT, min(self::MAX_RECENT_POOL_LIMIT, $poolLimit));

		$seedWindow = (int) ($data['seed_window_minutes'] ?? 60);
		$seedWindow = max(self::MIN_SEED_WINDOW_MINUTES, min(self::MAX_SEED_WINDOW_MINUTES, $seedWindow));

		return [
			'recent_window_days' => $recentWindowDays,
			'recent_shuffle_pool_limit' => $poolLimit,
			'seed_window_minutes' => $seedWindow,
		];
	}
}

