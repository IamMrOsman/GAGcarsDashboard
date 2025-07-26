<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserResourcesController extends Controller
{
	/**
	 * Get the listings uploaded by the specified user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userListings(User $user)
	{
		return response()->json($user->items);
	}

	/**
	 * Get the verifications uploaded by the specified user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userVerifications(User $user)
	{
		return response()->json($user->verifications);
	}

	/**
	 * Get the promotions uploaded by the specified user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userPromotions(User $user)
	{
		return response()->json($user->promotions);
	}

	/**
	 * Get the special offers uploaded by the specified user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function userSpecialOffers(User $user)
	{
		return response()->json($user->specialOffers);
	}

	/**
	 * Add an item to the authenticated user's wishlist
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
	 * Get the wishlist of the authenticated user
	 * @return JsonResponse
	 */
	public function wishList()
	{
		return response()->json(auth()->user()->wishList()->with('item')->get());
	}
}
