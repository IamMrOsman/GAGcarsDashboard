<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Brand;
use App\Models\Setting;
use App\Models\Category;
use App\Models\BrandModel;
use App\Models\FaqCategory;
use App\Models\CategoryRequirement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ItemPriceNotification;
use App\Models\Country;
use App\Services\UploadCreditPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppResourceController extends Controller
{
	/**
	 * Apply unified filters to an Item query.
	 */
	private function applyItemFilters(Builder|Relation $q, Request $request): Builder|Relation
	{
		$priceNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(price, ',', ''), ' ', ''), '₵', ''), '') AS UNSIGNED)";
		$mileageNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(mileage, ',', ''), ' ', ''), 'km', ''), '') AS UNSIGNED)";

		if ($request->filled('condition')) {
			$cond = strtolower(trim((string) $request->query('condition')));
			if ($cond === 'new') {
				$q->whereRaw('LOWER(`condition`) = ?', ['new']);
			} elseif ($cond === 'used') {
				$q->whereRaw('LOWER(`condition`) = ?', ['used']);
			}
		}

		$min = $request->query('price_min');
		$max = $request->query('price_max');
		if ($min !== null || $max !== null) {
			$minInt = $min !== null ? (int) $min : 0;
			$maxInt = $max !== null ? (int) $max : null;
			if ($maxInt !== null && $maxInt > 0) {
				$q->whereRaw("$priceNumericSql BETWEEN ? AND ?", [$minInt, $maxInt]);
			} else {
				$q->whereRaw("$priceNumericSql >= ?", [$minInt]);
			}
		}

		$yMin = $request->query('year_min');
		$yMax = $request->query('year_max');
		if ($yMin !== null || $yMax !== null) {
			$yMinInt = $yMin !== null ? (int) $yMin : 0;
			$yMaxInt = $yMax !== null ? (int) $yMax : null;
			if ($yMaxInt !== null && $yMaxInt > 0) {
				$q->whereBetween('year', [$yMinInt, $yMaxInt]);
			} else {
				$q->where('year', '>=', $yMinInt);
			}
		}

		$mMin = $request->query('mileage_min');
		$mMax = $request->query('mileage_max');
		if ($mMin !== null || $mMax !== null) {
			$mMinInt = $mMin !== null ? (int) $mMin : 0;
			$mMaxInt = $mMax !== null ? (int) $mMax : null;
			if ($mMaxInt !== null && $mMaxInt > 0) {
				$q->whereRaw("$mileageNumericSql BETWEEN ? AND ?", [$mMinInt, $mMaxInt]);
			} else {
				$q->whereRaw("$mileageNumericSql >= ?", [$mMinInt]);
			}
		}

		if ($request->filled('location')) {
			$q->where('location', (string) $request->query('location'));
		}

		return $q;
	}

	private function applyItemSort(Builder|Relation $q, Request $request, string $defaultSort = 'relevance'): Builder|Relation
	{
		$sort = strtolower(trim((string) ($request->query('sort') ?? $defaultSort)));
		$priceNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(price, ',', ''), ' ', ''), '₵', ''), '') AS UNSIGNED)";

		return match ($sort) {
			'newest' => $q->orderByDesc('created_at'),
			'oldest' => $q->orderBy('created_at'),
			'price_asc' => $q->orderByRaw("$priceNumericSql ASC"),
			'price_desc' => $q->orderByRaw("$priceNumericSql DESC"),
			default => $q->orderByDesc('updated_at'), // best-effort "relevance"
		};
	}

	private function paginateOrGet(Builder|Relation $q, Request $request)
	{
		$perPage = (int) ($request->query('per_page') ?? 15);
		$perPage = $perPage > 0 ? min($perPage, 200) : 15;

		return $q->paginate($perPage)->appends($request->query());
	}

	/**
	 * Pseudo-random order within the same similarity tier: stable for offset pagination (no ORDER BY RAND()),
	 * different sequence per viewed listing, cheap per-row expression.
	 */
	private function applySimilarItemsShuffleOrder(Builder $q, Item $item, int $countryId): void
	{
		$salt = (string) $item->getKey().'|'.$countryId;
		$table = $q->getModel()->getTable();
		$q->orderByRaw('CRC32(CONCAT(`'.$table.'`.`id`, ?))', [$salt]);
	}

	/**
	 * Resolve a brand from a route segment: numeric = id, otherwise slug (case-insensitive).
	 */
	protected function resolveBrandFromRouteSegment(string $brand): Brand
	{
		$key = trim($brand);
		if ($key === '') {
			abort(404);
		}
		if (preg_match('/^\d+$/', $key)) {
			return Brand::where('id', (int) $key)->firstOrFail();
		}

		return Brand::whereRaw('LOWER(slug) = ?', [strtolower($key)])->firstOrFail();
	}

	public function priceNotification(Item $item)
	{
		$user = auth()->user();

		if (!$item) {
			return response()->json(['message' => 'Item not found'], 404);
		}

		$existingNotification = $item->usersToNotifyOnPriceChange()->where('user_id', $user->id)->first();

		if ($existingNotification) {
			$existingNotification->delete();
		} else {
			$itemPriceNotification = ItemPriceNotification::firstOrCreate([
				'item_id' => $item->id,
				'user_id' => $user->id,
				'price' => $item->price,
			]);
		}

		return response()->json(['message' => 'Price notification updated successfully'], 201);
	}

	public function sendPriceNotifications(Item $item)
	{
		$usersToNotify = $item->usersToNotifyOnPriceChange()->get();

		foreach ($usersToNotify as $userToNotify) {
			$userToNotify->send();
		}
	}

	/**
	 * Similar listings (detail screen): same brand first, then same category, then other active
	 * items in the user’s country. Category tier uses $categoryIdForOrdering (from route or item).
	 */
	private function similarItemsPaginatedResponse(Item $item, int $categoryIdForOrdering): \Illuminate\Http\JsonResponse
	{
		$countryId = auth()->user()->country_id;
		$brandId = $item->brand_id;

		$q = Item::query()
			->with('brand', 'category', 'brandModel', 'user')
			->where('id', '!=', $item->id)
			->where('status', 'active')
			->where('country_id', $countryId);

		$this->applyItemFilters($q, request());

		if ($brandId) {
			if ($categoryIdForOrdering > 0) {
				$q->orderByRaw(
					'CASE WHEN brand_id = ? THEN 0 WHEN category_id = ? THEN 1 ELSE 2 END',
					[$brandId, $categoryIdForOrdering]
				);
			} else {
				$q->orderByRaw('CASE WHEN brand_id = ? THEN 0 ELSE 1 END', [$brandId]);
			}
		} else {
			if ($categoryIdForOrdering > 0) {
				$q->orderByRaw(
					'CASE WHEN category_id = ? THEN 0 ELSE 1 END',
					[$categoryIdForOrdering]
				);
			}
			// No brand/category hints: similarity tier is skipped; shuffle + applyItemSort define order.
		}

		$this->applySimilarItemsShuffleOrder($q, $item, (int) $countryId);
		$this->applyItemSort($q, request());

		return response()->json($this->paginateOrGet($q, request()));
	}

	/**
	 * Preferred endpoint for the mobile app: only the viewed item id is required (category comes from the item row).
	 */
	public function getSimilarItemsForItem(Item $item)
	{
		return $this->similarItemsPaginatedResponse($item, (int) ($item->category_id ?? 0));
	}

	/**
	 * @deprecated Prefer {@see getSimilarItemsForItem} — kept for older clients.
	 */
	public function getSimilarItemsByCategory(Category $category, Item $item)
	{
		return $this->similarItemsPaginatedResponse($item, (int) $category->id);
	}

	/**
	 * Get Similar Items by Brand ({brand} may be numeric id or slug).
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrand(string $brand, Item $item)
	{
		$brandModel = $this->resolveBrandFromRouteSegment($brand);

		$q = $brandModel->items()
			->with('brand', 'category', 'brandModel', 'user')
			->where('id', '!=', $item->id)
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);
		$this->applySimilarItemsShuffleOrder($q, $item, (int) auth()->user()->country_id);

		return response()->json($q->get());
	}

	/**
	 * Get Similar Items by Brand Model
	 * @param BrandModel $brandModel
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrandModel(BrandModel $brandModel, Item $item)
	{
		$q = $brandModel->items()
			->with('brand', 'category', 'brandModel', 'user')
			->where('id', '!=', $item->id)
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);
		$this->applySimilarItemsShuffleOrder($q, $item, (int) auth()->user()->country_id);

		return response()->json($q->get());
	}

	/**
	 * Get Category Items
	 * @param Category $category
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCategoryItems(Category $category)
	{
		$q = $category->items()
			->with('brand', 'category', 'brandModel', 'user')
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		$this->applyItemFilters($q, request());
		$this->applyItemSort($q, request());

		return response()->json($this->paginateOrGet($q, request()));
	}

	/**
	 * Get Brand Items ({brand} may be numeric id or slug, e.g. "toyota").
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getBrandItems(string $brand)
	{
		$brandModel = $this->resolveBrandFromRouteSegment($brand);

		$q = $brandModel->items()
			->with('brand', 'category', 'brandModel', 'user')
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		$this->applyItemFilters($q, request());
		$this->applyItemSort($q, request());

		return response()->json($this->paginateOrGet($q, request()));
	}

	/**
	 * Get Brand Model Items
	 * @param BrandModel $brandModel
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getBrandModelItems(BrandModel $brandModel)
	{
		$q = $brandModel->items()
			->with('brand', 'category', 'brandModel', 'user')
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		$this->applyItemFilters($q, request());
		$this->applyItemSort($q, request());

		return response()->json($this->paginateOrGet($q, request()));
	}

	/**
	 * Search Items
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function searchItems(Request $request)
	{
		$query = (string) $request->query('query', '');
		$query = trim($query);

		$q = Item::query()
			->with('brand', 'category', 'brandModel', 'user')
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->when($query !== '', function (Builder $builder) use ($query): void {
				$builder->where(function (Builder $w) use ($query): void {
					$w->where('name', 'like', '%'.$query.'%')
						->orWhereHas('category', fn ($q) => $q->where('name', 'like', '%'.$query.'%'))
						->orWhereHas('brand', fn ($q) => $q->where('name', 'like', '%'.$query.'%'))
						->orWhereHas('brandModel', fn ($q) => $q->where('name', 'like', '%'.$query.'%'));
				});
			});

		$this->applyItemFilters($q, $request);
		$this->applyItemSort($q, $request);

		return response()->json($this->paginateOrGet($q, $request));
	}

	/**
	 * Distinct locations for current country (for filter dropdown).
	 */
	public function getLocations(Request $request)
	{
		$user = $request->user();
		$countryId = $user?->country_id;
		if (! $countryId) {
			return response()->json(['data' => [], 'meta' => []]);
		}

		$locations = Item::query()
			->where('status', 'active')
			->where('country_id', $countryId)
			->whereNotNull('location')
			->where('location', '!=', '')
			->distinct()
			->orderBy('location')
			->pluck('location')
			->values();

		$priceNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(price, ',', ''), ' ', ''), '₵', ''), '') AS UNSIGNED)";
		$mileageNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(mileage, ',', ''), ' ', ''), 'km', ''), '') AS UNSIGNED)";

		$meta = Item::query()
			->where('status', 'active')
			->where('country_id', $countryId)
			->selectRaw("MAX($priceNumericSql) as price_max")
			->selectRaw("MAX($mileageNumericSql) as mileage_max")
			->selectRaw("MIN(year) as year_min")
			->selectRaw("MAX(year) as year_max")
			->first();

		return response()->json([
			'data' => $locations,
			'meta' => [
				'price_max' => (int) ($meta->price_max ?? 0),
				'mileage_max' => (int) ($meta->mileage_max ?? 0),
				'year_min' => (int) ($meta->year_min ?? 0),
				'year_max' => (int) ($meta->year_max ?? 0),
			],
		]);
	}

	/**
	 * Get Category Faqs
	 * @param FaqCategory $faqCategory
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCategoryFaqs(FaqCategory $faqCategory)
	{
		return response()->json($faqCategory->faqs);
	}

	/**
	 * Can Upload
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function canUpload(Request $request)
	{
		$user = auth()->user();
		$categoryId = $request->input('category_id');

		$paidUpload = UploadCreditPolicy::paidUploadApplies($categoryId, $user->country_id);

		if ($paidUpload) {
			$uploadsForCategory = $user->getUploadsLeftForCategory($categoryId);
			if ($uploadsForCategory <= 0) {
				return response()->json(['can_upload' => false, 'reason' => 'You have no uploads left for this category'], 200);
			}
			return response()->json([
				'can_upload' => true,
				'reason' => 'Upload credits apply; you have ' . $uploadsForCategory . ' uploads left for this category',
			], 200);
		}

		return response()->json([
			'can_upload' => true,
			'reason' => 'No upload credits required for this category',
		], 200);
	}

	/**
	 * Get Packages by Category
	 * @param Category $category
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getPackagesByCategory(Category $category)
	{
		return response()->json($category->packages()->where('country_id', auth()->user()->country_id)->get());
	}

	/**
	 * Get Countries
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCountries()
	{
		return response()->json(Country::all());
	}
}
