<?php

namespace App\Filament\Resources\PostResource\Api\Transformers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Post $resource
 */
class PostTransformer extends JsonResource
{
	/**
	 * @param  \Illuminate\Http\Request  $request
	 * @return array<string, mixed>
	 */
	public function toArray($request): array
	{
		/** @var Post $post */
		$post = $this->resource;

		$row = $post->attributesToArray();
		$row['tags'] = $post->tags ?? [];
		$row['image'] = self::resolvePublicStorageUrl($post->image, $request);

		if ($post->created_at) {
			$row['created_at'] = $post->created_at->toIso8601String();
		}
		if ($post->updated_at) {
			$row['updated_at'] = $post->updated_at->toIso8601String();
		}

		if ($post->relationLoaded('category') && $post->category instanceof PostCategory) {
			$row['category'] = self::categoryToArray($post->category, $request);
		}

		return $row;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function categoryToArray(PostCategory $cat, $request): array
	{
		$c = $cat->attributesToArray();
		$c['image'] = self::resolvePublicStorageUrl($cat->image, $request);
		if ($cat->created_at) {
			$c['created_at'] = $cat->created_at->toIso8601String();
		}
		if ($cat->updated_at) {
			$c['updated_at'] = $cat->updated_at->toIso8601String();
		}

		return $c;
	}

	/**
	 * Absolute URL under /storage so mobile clients do not double-prefix paths.
	 * Strips redundant "storage/" segments from DB values.
	 */
	public static function resolvePublicStorageUrl(?string $stored, $request): string
	{
		if ($stored === null || trim($stored) === '') {
			return '';
		}

		$stored = trim($stored);
		if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
			return $stored;
		}

		if (str_starts_with($stored, '[')) {
			$decoded = json_decode($stored, true);
			if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0]) && $decoded[0] !== '') {
				$stored = trim($decoded[0]);
			}
		}

		$path = ltrim($stored, '/');
		while (str_starts_with($path, 'storage/')) {
			$path = substr($path, strlen('storage/'));
		}
		if (str_starts_with($path, 'public/')) {
			$path = substr($path, strlen('public/'));
		}
		$path = ltrim($path, '/');

		if ($path === '') {
			return '';
		}

		$configured = config('gagcars.public_storage_base_url');
		if (is_string($configured) && $configured !== '') {
			return rtrim($configured, '/') . '/storage/' . $path;
		}

		if ($request !== null && method_exists($request, 'getSchemeAndHttpHost')) {
			$host = $request->getHttpHost();
			if ($host !== '') {
				$scheme = $request->getScheme();

				return $scheme . '://' . $host . '/storage/' . $path;
			}
		}

		return rtrim((string) config('app.url', ''), '/') . '/storage/' . $path;
	}
}
