<?php

namespace App\Filament\Resources\BroadcastResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\BroadcastResource;
use App\Filament\Resources\BroadcastResource\Api\Requests\UpdateBroadcastRequest;

class UpdateHandler extends Handlers
{
	public static string | null $uri = '/{id}';
	public static string | null $resource = BroadcastResource::class;

	public static function getMethod()
	{
		return Handlers::PUT;
	}

	public static function getModel()
	{
		return static::$resource::getModel();
	}


	/**
	 * Update Broadcast
	 *
	 * @param UpdateBroadcastRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function handler(UpdateBroadcastRequest $request)
	{
		$id = $request->route('id');

		$model = static::getModel()::find($id);

		if (!$model) return static::sendNotFoundResponse();

		$model->fill($request->all());

		$model->save();

		return static::sendSuccessResponse($model, "Successfully Update Broadcast");
	}
}

