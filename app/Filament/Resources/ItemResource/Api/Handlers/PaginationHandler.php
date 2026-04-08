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
		$settings = HomeAdsSettingsService::get();

		$query = static::getEloquentQuery();

		$query = QueryBuilder::for($query)
			->allowedFields($this->getAllowedFields() ?? [])
			->allowedSorts($this->getAllowedSorts() ?? [])
			->allowedFilters($this->getAllowedFilters() ?? [])
			->allowedIncludes($this->getAllowedIncludes() ?? [])
			->with(['brand', 'category.itemFields', 'brandModel', 'user', 'promotions'])
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->select(['user_id', 'id', 'name', 'price', 'country_id', 'location', 'category_id', 'brand_id', 'brand_model_id', 'condition', 'description', 'images', 'serial_number', 'status', 'warranty', 'transmission', 'mileage'])
			->paginate(request()->query('per_page'))
			->appends(request()->query());

		$items = $query->getCollection();
		$now = now();

		if (!($settings['enabled'] ?? false) || ($settings['max_featured_per_page'] ?? 0) <= 0) {
			return ItemTransformer::collection($query);
		}

		$injectEveryN = (int) ($settings['inject_every_n'] ?? 8);
		$maxFeatured = (int) ($settings['max_featured_per_page'] ?? 3);
		$noAdjacent = (bool) ($settings['no_adjacent_featured'] ?? true);
		$dedupe = (bool) ($settings['dedupe_within_page'] ?? true);
		$seedWindow = (int) ($settings['rotation_seed_window_minutes'] ?? 60);

		// Build a pool of promoted items (active promotions), stable-rotated by a seed.
		$page = (int) request()->query('page', 1);
		$perPage = (int) request()->query('per_page', 15);

		$window = max(1, $seedWindow) * 60;
		$seedSlot = (int) floor(time() / $window);
		$seed = crc32(auth()->id() . '|' . auth()->user()->country_id . '|' . $seedSlot . '|' . $page);

		$promotedPool = Item::query()
			->with(['brand', 'category.itemFields', 'brandModel', 'user', 'promotions'])
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->whereHas('promotions', function ($q) use ($now) {
				$q->where('start_at', '<=', $now)->where('end_at', '>=', $now);
			})
			// Keep pool size bounded; we only need a few per page.
			->inRandomOrder($seed)
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
