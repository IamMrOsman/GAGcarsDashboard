<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Brand;
use App\Models\Category;
use App\Models\BrandModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ItemPriceNotification;

class AppResourceController extends Controller
{
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
	 * Get Similar Items by Category
	 * @param Category $category
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByCategory(Category $category, Item $item)
	{
		return response()->json($category->items()->where('id', '!=', $item->id)->get());
	}

	/**
	 * Get Similar Items by Brand
	 * @param Brand $brand
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrand(Brand $brand, Item $item)
	{
		return response()->json($brand->items()->where('id', '!=', $item->id)->get());
	}

	/**
	 * Get Similar Items by Brand Model
	 * @param BrandModel $brandModel
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrandModel(BrandModel $brandModel, Item $item)
	{
		return response()->json($brandModel->items()->where('id', '!=', $item->id)->get());
	}

	/**
	 * Get Category Items
	 * @param Category $category
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCategoryItems(Category $category)
	{
		return response()->json($category->items);
	}

	/**
	 * Get Brand Items
	 * @param Brand $brand
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getBrandItems(Brand $brand)
	{
		return response()->json($brand->items);
	}

	/**
	 * Get Brand Model Items
	 * @param BrandModel $brandModel
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getBrandModelItems(BrandModel $brandModel)
	{
		return response()->json($brandModel->items);
	}

	/**
	 * Search Items
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function searchItems(Request $request)
	{
		$query = $request->query('query');

		// Search for items by name
		$itemsByName = Item::where('name', 'like', '%' . $query . '%');

		// Search for items by category name
		$itemsByCategory = Item::whereHas('category', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		});

		// Search for items by brand name
		$itemsByBrand = Item::whereHas('brand', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		});

		// Search for items by brand model name
		$itemsByBrandModel = Item::whereHas('brandModel', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		});

		// Combine all queries and get unique results
		$results = $itemsByName
			->union($itemsByCategory)
			->union($itemsByBrand)
			->union($itemsByBrandModel)
			->get()
			->unique('id');

		return response()->json($results);
	}
}
