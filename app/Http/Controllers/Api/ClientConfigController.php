<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PusherSettingsService;
use Illuminate\Http\JsonResponse;

class ClientConfigController extends Controller
{
	/**
	 * Public client-safe Pusher fields for mobile apps (no secret).
	 */
	public function __invoke(): JsonResponse
	{
		return response()->json(PusherSettingsService::getPublicClientConfig());
	}
}
