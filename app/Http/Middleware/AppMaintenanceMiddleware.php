<?php

namespace App\Http\Middleware;

use App\Services\AppMaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppMaintenanceMiddleware
{
	/**
	 * Reject requests during app maintenance mode.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if (!AppMaintenanceService::isEnabled()) {
			return $next($request);
		}

		$path = ltrim($request->path(), '/');
		$allowed = [
			'api/client-config',
			'api/sanctum/token',
			'api/sanctum/register',
			'api/paystack/webhook',
			'up',
		];

		if (in_array($path, $allowed, true)) {
			return $next($request);
		}

		return response()->json([
			'message' => 'App is under maintenance. Please try again later.',
			'maintenance' => true,
		], 503);
	}
}

