<?php

namespace App\Services;

use App\Models\Setting;

class HomeAdsSettingsService
{
	public const MIN_INJECT_EVERY_N = 2;
	public const MAX_INJECT_EVERY_N = 50;

	public const MIN_MAX_FEATURED_PER_PAGE = 0;
	public const MAX_MAX_FEATURED_PER_PAGE = 20;

	public const MIN_SEED_WINDOW_MINUTES = 1;
	public const MAX_SEED_WINDOW_MINUTES = 24 * 60;

	public static function get(): array
	{
		$row = Setting::where('key_slug', 'home_ads')->first();
		$data = ($row && is_array($row->data)) ? $row->data : [];

		$enabled = filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOL);

		$injectEveryN = (int) ($data['inject_every_n'] ?? 8);
		$injectEveryN = max(self::MIN_INJECT_EVERY_N, min(self::MAX_INJECT_EVERY_N, $injectEveryN));

		$maxFeaturedPerPage = (int) ($data['max_featured_per_page'] ?? 3);
		$maxFeaturedPerPage = max(self::MIN_MAX_FEATURED_PER_PAGE, min(self::MAX_MAX_FEATURED_PER_PAGE, $maxFeaturedPerPage));

		$noAdjacentFeatured = filter_var($data['no_adjacent_featured'] ?? true, FILTER_VALIDATE_BOOL);
		$dedupeWithinPage = filter_var($data['dedupe_within_page'] ?? true, FILTER_VALIDATE_BOOL);

		$seedWindow = (int) ($data['rotation_seed_window_minutes'] ?? 60);
		$seedWindow = max(self::MIN_SEED_WINDOW_MINUTES, min(self::MAX_SEED_WINDOW_MINUTES, $seedWindow));

		return [
			'enabled' => $enabled,
			'inject_every_n' => $injectEveryN,
			'max_featured_per_page' => $maxFeaturedPerPage,
			'no_adjacent_featured' => $noAdjacentFeatured,
			'dedupe_within_page' => $dedupeWithinPage,
			'rotation_seed_window_minutes' => $seedWindow,
		];
	}
}

