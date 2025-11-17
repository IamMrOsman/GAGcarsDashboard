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
use App\Services\PaymentRequirementService;
use App\Models\Country;

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
		return response()->json($category->items()->with('brand', 'category', 'brandModel', 'user')->where('id', '!=', $item->id)->get());
	}

	/**
	 * Get Similar Items by Brand
	 * @param Brand $brand
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrand(Brand $brand, Item $item)
	{
		return response()->json($brand->items()->with('brand', 'category', 'brandModel', 'user')->where('id', '!=', $item->id)->get());
	}

	/**
	 * Get Similar Items by Brand Model
	 * @param BrandModel $brandModel
	 * @param Item $item
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarItemsByBrandModel(BrandModel $brandModel, Item $item)
	{
		return response()->json($brandModel->items()->with('brand', 'category', 'brandModel', 'user')->where('id', '!=', $item->id)->get());
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
		return response()->json($brand->items()->with('brand', 'category', 'brandModel', 'user')->get());
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
		$itemsByName = Item::where('name', 'like', '%' . $query . '%')
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		// Search for items by category name
		$itemsByCategory = Item::whereHas('category', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		})
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		// Search for items by brand name
		$itemsByBrand = Item::whereHas('brand', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		})
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		// Search for items by brand model name
		$itemsByBrandModel = Item::whereHas('brandModel', function ($q) use ($query) {
			$q->where('name', 'like', '%' . $query . '%');
		})
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id);

		// Combine all queries and get unique results
		$results = $itemsByName
			->union($itemsByCategory)
			->union($itemsByBrand)
			->union($itemsByBrandModel)
			->get()
			->unique('id')
			->load('brand', 'category', 'brandModel', 'user');

		return response()->json($results);
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

		// Check if payment is required for this category in the user's country
		$paymentRequired = CategoryRequirement::where('category_id', $categoryId)
			->where('country_id', $user->country_id)
			->where('require_payment', true)
			->exists();

		// If payment is required, check if user has uploads left
		if ($paymentRequired) {
			if ($user->uploads_left <= 0) {
				return response()->json(['can_upload' => false, 'reason' => 'You have no uploads left'], 200);
			}
		}

		return response()->json([
			'can_upload' => true,
			'reason' => $paymentRequired ? 'Payment required but you have ' . $user->uploads_left . ' uploads left' : 'Payment not required for this category'
		], 200);
	}

	/**
	 * Get Packages by Category
	 * @param Category $category
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getPackagesByCategory(Category $category)
	{
		return response()->json($category->packages);
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
