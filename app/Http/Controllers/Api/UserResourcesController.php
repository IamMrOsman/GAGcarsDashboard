<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserResourcesController extends Controller
{
	private function applyItemFiltersToWishlistItemQuery(Builder $q, Request $request): Builder
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

	private function applyItemSortToWishlistItemQuery(Builder $q, Request $request, string $defaultSort = 'relevance'): Builder
	{
		$sort = strtolower(trim((string) ($request->query('sort') ?? $defaultSort)));
		$priceNumericSql = "CAST(NULLIF(REPLACE(REPLACE(REPLACE(price, ',', ''), ' ', ''), '₵', ''), '') AS UNSIGNED)";

		return match ($sort) {
			'newest' => $q->orderByDesc('created_at'),
			'oldest' => $q->orderBy('created_at'),
			'price_asc' => $q->orderByRaw("$priceNumericSql ASC"),
			'price_desc' => $q->orderByRaw("$priceNumericSql DESC"),
			default => $q->orderByDesc('updated_at'),
		};
	}

	private function perPage(Request $request): int
	{
		$perPage = (int) ($request->query('per_page') ?? 15);
		return $perPage > 0 ? min($perPage, 200) : 15;
	}

	/**
	 * User's Listings
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function userListings(User $user)
	{
		return ItemTransformer::collection(
			$user->items()
				->with('category')
				->where('status', 'active')
				->orderByDesc('created_at')
				->get()
		);
	}

	/**
	 * User details
	 */
	public function userDetails(User $user)
	{
		$user->loadCount(['items' => function ($query) {
			$query->whereNull('deleted_at')->where('status', 'active');
		}]);

		return response()->json([
			'user' => $user,
			'verified' => $user->isVerified(),
			'verified_dealer' => $user->isVerifiedDealer()
		]);
	}

	/**
	 * My Verifications
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function myVerifications()
	{
		return response()->json(auth()->user()->verifications);
	}

	/**
	 * User's Promotions
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userPromotions(User $user)
	{
		return response()->json($user->promotions);
	}

	/**
	 * User's Special Offers
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userSpecialOffers(User $user)
	{
		return response()->json($user->specialOffers);
	}

	/**
	 * Add to Wishlist
	 * @param Item $item
	 * @return JsonResponse
	 */
	public function addToWishList(Item $item): JsonResponse
	{
		try {
			$user = auth()->user();

			// Check if item is already in user's wishlist
			$existingWishlist = $user->wishList()
				->where('item_id', $item->id)
				->first();

			if ($existingWishlist) {
				// Delete the existing wishlist entry
				$existingWishlist->delete();

				return response()->json([
					'success' => true,
					'message' => 'Item removed from wishlist',
				], 200);
			}

			// Check if user is trying to add their own item to wishlist
			if ($item->user_id === $user->id) {
				return response()->json([
					'success' => false,
					'message' => 'You cannot add your own item to wishlist',
					'data' => null
				], 400);
			}

			// Create wishlist entry
			$wishlist = $user->wishList()->create([
				'item_id' => $item->id
			]);

			return response()->json([
				'success' => true,
				'message' => 'Item added to wishlist successfully',
			], 201);
		} catch (\Exception $e) {
			Log::error('Error managing wishlist', [
				'user_id' => auth()->id(),
				'item_id' => $item->id,
				'error' => $e->getMessage()
			]);

			return response()->json([
				'success' => true,
				'message' => 'Failed to manage wishlist. Please try again.',
				'data' => null
			], 500);
		}
	}

	/**
	 * My Wishlist
	 * @return JsonResponse
	 */
	public function wishList(Request $request)
	{
		$user = $request->user();
		$countryId = $user?->country_id;

		$query = $user->wishList()
			->with('item.category')
			->whereHas('item', function (Builder $itemQ) use ($countryId, $request): void {
				$itemQ
					->where('status', 'active')
					->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId));

				$this->applyItemFiltersToWishlistItemQuery($itemQ, $request);
				$this->applyItemSortToWishlistItemQuery($itemQ, $request);
			});

		return response()->json($query->paginate($this->perPage($request))->appends($request->query()));
	}

	/**
	 * My Listings
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function myListings()
	{
		// My Listings should only include real listings (exclude drafts).
		return ItemTransformer::collection(
			auth()->user()->items()
				->with('category')
				->whereNotIn('status', ['draft', 'pending_payment'])
				->get()
		);
	}

	/**
	 * My Notifications
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function notifications()
	{
		return response()->json(auth()->user()->notifications);
	}
}
