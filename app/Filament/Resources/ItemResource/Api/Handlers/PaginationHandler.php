<?php

namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;
use App\Models\Item;
use App\Services\HomeAdsSettingsService;
use App\Services\HomeFeedSettingsService;

class PaginationHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = ItemResource::class;


	/**
	 * List of Item
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function handler()
	{
		$adsSettings = HomeAdsSettingsService::get();
		$feedSettings = HomeFeedSettingsService::get();

		$query = static::getEloquentQuery();

		$query = QueryBuilder::for($query)
			->allowedFields($this->getAllowedFields() ?? [])
			->allowedSorts($this->getAllowedSorts() ?? [])
			->allowedFilters($this->getAllowedFilters() ?? [])
			->allowedIncludes($this->getAllowedIncludes() ?? [])
			->with(['brand', 'category.itemFields', 'brandModel', 'user', 'promotions'])
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->orderByDesc('created_at')
			->select(['user_id', 'id', 'name', 'price', 'country_id', 'location', 'category_id', 'brand_id', 'brand_model_id', 'condition', 'description', 'images', 'serial_number', 'status', 'warranty', 'transmission', 'mileage'])
			->paginate(request()->query('per_page'))
			->appends(request()->query());

		$items = $query->getCollection();
		$now = now();

		// Seed: client can pass seed for stability across pages; otherwise derive from seed-window.
		$page = (int) request()->query('page', 1);
		$seedStr = (string) request()->query('seed', '');
		$seed = $seedStr !== '' ? crc32($seedStr . '|' . $page) : $this->seedFromWindow((int) ($feedSettings['seed_window_minutes'] ?? 60), $page);

		// Apply variety to organic list: deterministic shuffle within recent window (bounded).
		$items = $this->shuffleRecentOrganic($items, $seed, (int) $feedSettings['recent_window_days'], (int) $feedSettings['recent_shuffle_pool_limit']);
		$query->setCollection($items);

		if (!($adsSettings['enabled'] ?? false) || ($adsSettings['max_featured_per_page'] ?? 0) <= 0) {
			return ItemTransformer::collection($query);
		}

		$injectEveryN = (int) ($adsSettings['inject_every_n'] ?? 8);
		$maxFeatured = (int) ($adsSettings['max_featured_per_page'] ?? 3);
		$noAdjacent = (bool) ($adsSettings['no_adjacent_featured'] ?? true);
		$dedupe = (bool) ($adsSettings['dedupe_within_page'] ?? true);
		$seedWindow = (int) ($adsSettings['rotation_seed_window_minutes'] ?? 60);

		// Build a pool of promoted items (active promotions), stable-rotated by a seed.
		$perPage = (int) request()->query('per_page', 15);

		$window = max(1, $seedWindow) * 60;
		$seedSlot = (int) floor(time() / $window);
		$promoSeed = crc32(auth()->id() . '|' . auth()->user()->country_id . '|' . $seedSlot . '|' . $page . '|' . ($seedStr !== '' ? $seedStr : ''));

		$promotedPool = Item::query()
			->with(['brand', 'category.itemFields', 'brandModel', 'user', 'promotions'])
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->whereHas('promotions', function ($q) use ($now) {
				$q->where('start_at', '<=', $now)->where('end_at', '>=', $now);
			})
			// Keep pool size bounded; we only need a few per page.
			->inRandomOrder($promoSeed)
			->limit(max(20, $maxFeatured * 8))
			->get();

		$mixed = $this->injectFeaturedInline(
			$items,
			$promotedPool,
			$injectEveryN,
			$maxFeatured,
			$noAdjacent,
			$dedupe
		);

		$query->setCollection($mixed);

		return ItemTransformer::collection($query);
	}

	private function seedFromWindow(int $seedWindowMinutes, int $page): int
	{
		$window = max(1, $seedWindowMinutes) * 60;
		$seedSlot = (int) floor(time() / $window);
		return crc32(auth()->id() . '|' . auth()->user()->country_id . '|' . $seedSlot . '|' . $page);
	}

	/**
	 * Deterministically shuffle only items within a recent window.
	 */
	private function shuffleRecentOrganic(Collection $organic, int $seed, int $recentWindowDays, int $poolLimit): Collection
	{
		$recentWindowDays = max(1, $recentWindowDays);
		$poolLimit = max(1, $poolLimit);

		$cutoff = now()->subDays($recentWindowDays);

		$recent = $organic->filter(function ($item) use ($cutoff) {
			if (!is_object($item) || !isset($item->created_at)) return false;
			try {
				return $item->created_at >= $cutoff;
			} catch (\Throwable) {
				return false;
			}
		});

		// Bound the shuffle pool (newest-first already); take first N recent items.
		$recent = $recent->take($poolLimit)->values();

		if ($recent->count() <= 1) {
			return $organic;
		}

		// Shuffle deterministically by sorting on a seeded hash.
		$shuffled = $recent->sortBy(function ($item) use ($seed) {
			$id = is_object($item) && isset($item->id) ? (string) $item->id : '';
			return crc32($seed . '|' . $id);
		})->values();

		$recentIds = $recent->map(fn ($i) => (string) $i->id)->all();
		$idxById = array_flip($recentIds);
		$pos = 0;

		// Replace the recent subset in-place, preserving non-recent ordering.
		return $organic->map(function ($item) use ($idxById, $shuffled, &$pos) {
			$id = is_object($item) && isset($item->id) ? (string) $item->id : null;
			if ($id !== null && isset($idxById[$id]) && $pos < $shuffled->count()) {
				$replacement = $shuffled[$pos];
				$pos++;
				return $replacement;
			}
			return $item;
		});
	}

	/**
	 * @param Collection<int, mixed> $organic
	 * @param Collection<int, mixed> $promoted
	 */
	private function injectFeaturedInline(
		Collection $organic,
		Collection $promoted,
		int $injectEveryN,
		int $maxFeatured,
		bool $noAdjacent,
		bool $dedupeWithinPage
	): Collection {
		$injectEveryN = max(1, $injectEveryN);
		$maxFeatured = max(0, $maxFeatured);

		if ($maxFeatured === 0 || $promoted->isEmpty()) {
			return $organic;
		}

		$out = collect();

		$usedIds = [];
		$promotedIndex = 0;
		$featuredInserted = 0;
		$sinceLastFeatured = 0;
		$lastWasFeatured = false;

		foreach ($organic as $item) {
			$id = is_object($item) && isset($item->id) ? (string) $item->id : null;
			if ($dedupeWithinPage && $id !== null) {
				if (isset($usedIds[$id])) {
					continue;
				}
				$usedIds[$id] = true;
			}

			$out->push($item);
			$sinceLastFeatured++;
			$lastWasFeatured = false;

			$shouldInject = $sinceLastFeatured >= $injectEveryN;
			if (!$shouldInject) {
				continue;
			}
			if ($featuredInserted >= $maxFeatured) {
				continue;
			}
			if ($noAdjacent && $lastWasFeatured) {
				continue;
			}

			// Find next promoted item not yet used in this page.
			while ($promotedIndex < $promoted->count()) {
				$p = $promoted[$promotedIndex];
				$promotedIndex++;

				$pid = is_object($p) && isset($p->id) ? (string) $p->id : null;
				if ($dedupeWithinPage && $pid !== null && isset($usedIds[$pid])) {
					continue;
				}

				$out->push($p);
				if ($dedupeWithinPage && $pid !== null) {
					$usedIds[$pid] = true;
				}

				$featuredInserted++;
				$sinceLastFeatured = 0;
				$lastWasFeatured = true;
				break;
			}
		}

		return $out;
	}
}
