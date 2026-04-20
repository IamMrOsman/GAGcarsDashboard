<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Filament\Resources\BroadcastResource\Api\Transformers\BroadcastTransformer;
use Illuminate\Http\Request;

/**
 * Mobile app inbox: lists broadcasts the user is allowed to see.
 * Matches filtering in Filament {@see \App\Filament\Resources\BroadcastResource\Api\Handlers\PaginationHandler}.
 */
class BroadcastListController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();

		$query = Broadcast::query()
			->with(['user', 'country'])
			->where('status', 'sent')
			->where('country_id', $user->country_id)
			->where(function ($q) use ($user) {
				$q->where('target', 'all')
					->orWhere('target', $user->paid_seller ? 'dealers' : 'customers');
			})
			->latest('created_at');

		$perPage = (int) $request->query('per_page', 15);
		$perPage = min(max($perPage, 1), 50);

		$page = (int) $request->query('page', 1);
		$page = max($page, 1);

		$paginator = $query->paginate($perPage, ['*'], 'page', $page);

		return BroadcastTransformer::collection($paginator);
	}
}
