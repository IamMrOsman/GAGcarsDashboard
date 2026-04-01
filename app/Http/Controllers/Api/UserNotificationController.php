<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();

		$items = UserNotification::query()
			->where('user_id', $user->id)
			->latest()
			->limit(200)
			->get()
			->map(fn(UserNotification $n) => [
				'id' => $n->id,
				'title' => $n->title,
				'message' => $n->message,
				'is_read' => (bool) $n->is_read,
				'created_at' => $n->created_at?->toISOString(),
				'notification_type' => $n->notification_type,
				'data' => $n->data,
			]);

		return response()->json($items);
	}

	public function markAsRead(Request $request, UserNotification $notification)
	{
		$user = $request->user();

		if ($notification->user_id !== $user->id) {
			return response()->json(['message' => 'Forbidden'], 403);
		}

		$notification->update(['is_read' => true]);

		return response()->json(['success' => true]);
	}

	public function destroyAll(Request $request)
	{
		$user = $request->user();
		UserNotification::query()->where('user_id', $user->id)->delete();

		return response()->json(['success' => true]);
	}
}

