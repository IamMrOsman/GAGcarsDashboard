<?php

namespace App\Filament\Clusters\Settings\Resources\SettingResource\Api\Handlers;

use App\Filament\Clusters\Settings\Resources\SettingResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Clusters\Settings\Resources\SettingResource\Api\Transformers\SettingTransformer;

class DetailHandler extends Handlers
{
	public static string | null $uri = '/{id}';
	public static string | null $resource = SettingResource::class;


	/**
	 * Show Setting
	 *
	 * @param Request $request
	 * @return SettingTransformer
	 */
	public function handler(Request $request)
	{
		$id = $request->route('id');

		$query = static::getEloquentQuery();

		$query = QueryBuilder::for(
			$query->where(static::getKeyName(), $id)
		)
			->first();

		if (!$query) return static::sendNotFoundResponse();

		return new SettingTransformer($query);
	}
}
