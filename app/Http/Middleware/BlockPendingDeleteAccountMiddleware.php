<?php

namespace App\Http\Middleware;

use App\Models\DeleteAccountRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockPendingDeleteAccountMiddleware
{
	public function handle(Request $request, Closure $next): Response
	{
		$user = $request->user();
		if (! $user) {
			return $next($request);
		}

		$path = ltrim($request->path(), '/');
		$allowed = [
			'api/auth/logout',
			'api/user', // allow auth check to fail gracefully
			'api/delete-account/request',
		];

		if (in_array($path, $allowed, true)) {
			return $next($request);
		}

		$hasPending = DeleteAccountRequest::where('user_id', $user->id)
			->where('status', 'pending')
			->exists();

		if (! $hasPending) {
			return $next($request);
		}

		return response()->json([
			'message' => 'Your account is pending deletion approval. Access is temporarily restricted.',
			'code' => 'ACCOUNT_DELETE_PENDING',
		], 423);
	}
}

