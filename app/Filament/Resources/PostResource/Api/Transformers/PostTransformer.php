<?php

namespace App\Filament\Resources\PostResource\Api\Transformers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $row['image'] = self::resolveMediaUrl($post->image);

        if ($post->created_at) {
            $row['created_at'] = $post->created_at->toIso8601String();
        }
        if ($post->updated_at) {
            $row['updated_at'] = $post->updated_at->toIso8601String();
        }

        if ($post->relationLoaded('category') && $post->category instanceof PostCategory) {
            $row['category'] = self::categoryToArray($post->category);
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private static function categoryToArray(PostCategory $cat): array
    {
        $c = $cat->attributesToArray();
        $c['image'] = self::resolveMediaUrl($cat->image);
        if ($cat->created_at) {
            $c['created_at'] = $cat->created_at->toIso8601String();
        }
        if ($cat->updated_at) {
            $c['updated_at'] = $cat->updated_at->toIso8601String();
        }

        return $c;
    }

    /**
     * Build a browser-usable URL for Filament-stored paths (public disk).
     * Handles optional JSON encoding from some Filament versions.
     */
    public static function resolveMediaUrl(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        $stored = trim($stored);
        if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
            return $stored;
        }

        if (str_starts_with($stored, '[')) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0]) && $decoded[0] !== '') {
                $stored = $decoded[0];
            }
        }

        $stored = ltrim($stored, '/');

        return Storage::disk('public')->url($stored);
    }
}
