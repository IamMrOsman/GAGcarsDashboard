<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Filament\Resources\PostResource\Api\Transformers\PostTransformer;
use Illuminate\Http\Request;

/**
 * Mobile blog feed — published posts with public image URLs.
 */
class PostController extends Controller
{
	public function index(Request $request)
	{
		$perPage = (int) $request->query('per_page', 15);
		$perPage = min(max($perPage, 1), 50);

		$query = Post::query()
			->where('status', 'published')
			->with('category')
			->orderByDesc('created_at');

		if ($request->filled('category_id')) {
			$query->where('category_id', (int) $request->query('category_id'));
		}

		if ($request->filled('category')) {
			$slug = strtolower((string) $request->query('category'));
			$query->whereHas('category', static fn ($q) => $q->where('slug', $slug));
		}

		return PostTransformer::collection($query->paginate($perPage));
	}

	public function show(string $id)
	{
		$post = Post::query()
			->where('id', $id)
			->where('status', 'published')
			->with('category')
			->first();

		if ($post === null) {
			return response()->json(['message' => 'Not found'], 404);
		}

		return new PostTransformer($post);
	}
}
