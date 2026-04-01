<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceTokenController extends Controller
{
	public function store(Request $request)
	{
		$data = $request->validate([
			'token' => ['required', 'string'],
			'platform' => ['nullable', 'string', 'max:32'],
			'device_id' => ['nullable', 'string', 'max:128'],
		]);

		$user = $request->user();

		$token = trim($data['token']);
		$tokenHash = hash('sha256', $token);

		DeviceToken::updateOrCreate(
			['token_hash' => $tokenHash],
			[
				'user_id' => $user->id,
				'token' => $token,
				'platform' => $data['platform'] ?? null,
				'device_id' => $data['device_id'] ?? null,
				'last_used_at' => now(),
			]
		);

		return response()->json(['success' => true]);
	}

	public function destroy(Request $request)
	{
		$data = $request->validate([
			'token' => ['required', 'string'],
		]);

		$user = $request->user();
		$tokenHash = hash('sha256', trim($data['token']));

		$deleted = DeviceToken::query()
			->where('user_id', $user->id)
			->where('token_hash', $tokenHash)
			->delete();

		Log::info('Device token unregistered', ['user_id' => $user->id, 'deleted' => $deleted]);

		return response()->json(['success' => true]);
	}
}

