<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;

class UserResourcesController extends Controller
{
	/**
	 * User's Listings
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function userListings(User $user)
	{
		return ItemTransformer::collection($user->items);
	}

	/**
	 * User details
	 */
	public function userDetails(User $user)
	{
		return response()->json([
			'user' => $user->withCount('items')->first(),
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
	public function wishList()
	{
		return response()->json(auth()->user()->wishList()->with('item')->get());
	}

	/**
	 * My Listings
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function myListings()
	{
		return ItemTransformer::collection(auth()->user()->items);
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
