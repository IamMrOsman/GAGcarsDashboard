<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BroadcastLastSeenController extends Controller
{
	public function show(Request $request)
	{
		$at = $request->user()->broadcast_last_seen_at;

		return response()->json([
			'last_seen_at' => $at?->toIso8601String(),
		]);
	}

	public function store(Request $request)
	{
		$request->validate([
			'last_seen_at' => ['required', 'date'],
		]);

		$user = $request->user();
		$incoming = Carbon::parse($request->input('last_seen_at'))->utc();
		$current = $user->broadcast_last_seen_at;

		if ($current === null || $incoming->greaterThan($current)) {
			$user->update(['broadcast_last_seen_at' => $incoming]);
		}

		return response()->json(['success' => true]);
	}
}
