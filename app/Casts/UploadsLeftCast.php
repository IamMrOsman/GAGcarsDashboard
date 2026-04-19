<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;

/**
 * Normalizes legacy uploads_left storage (integer, JSON list like [2]) into an
 * associative map so API JSON always matches category-keyed objects.
 */
class UploadsLeftCast implements CastsAttributes
{
	public function get($model, string $key, $value, array $attributes): ?array
	{
		if (! array_key_exists($key, $attributes) || $attributes[$key] === null || $attributes[$key] === '') {
			return null;
		}

		$raw = $attributes[$key];

		if (is_array($raw)) {
			return self::normalize($raw);
		}

		if (is_int($raw) || is_float($raw)) {
			return self::normalize($raw);
		}

		$decoded = Json::decode((string) $raw, true);

		return self::normalize($decoded);
	}

	public function set($model, string $key, $value, array $attributes): array
	{
		if ($value === null) {
			return [$key => null];
		}

		$normalized = self::normalize($value);

		if ($normalized === null) {
			return [$key => null];
		}

		return [$key => Json::encode($normalized)];
	}

	public static function normalize(mixed $decoded): ?array
	{
		if ($decoded === null) {
			return null;
		}

		if (is_int($decoded) || is_float($decoded)) {
			return ['all' => (int) $decoded];
		}

		if (! is_array($decoded)) {
			return null;
		}

		if ($decoded === []) {
			return [];
		}

		if (array_is_list($decoded)) {
			if (count($decoded) === 1 && is_numeric($decoded[0])) {
				return ['all' => (int) $decoded[0]];
			}

			return ['all' => (int) array_sum(array_map(static fn ($v) => (int) $v, $decoded))];
		}

		return $decoded;
	}
}
