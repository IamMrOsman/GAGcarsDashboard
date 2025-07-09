<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserResourcesController extends Controller
{
    /**
	 * Get the listings uploaded by the authenticated user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function myListings()
	{
		return response()->json(auth()->user()->items);
	}

	/**
	 * Get the verifications uploaded by the authenticated user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function myVerifications()
	{
		return response()->json(auth()->user()->verifications);
	}

	/**
	 * Get the promotions uploaded by the authenticated user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function myPromotions()
	{
		return response()->json(auth()->user()->promotions);
	}

	/**
	 * Get the special offers uploaded by the authenticated user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function mySpecialOffers()
	{
		return response()->json(auth()->user()->specialOffers);
	}
}
