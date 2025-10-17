<?php

namespace App\Filament\Resources\BroadcastResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\BroadcastResource;
use App\Filament\Resources\BroadcastResource\Api\Requests\CreateBroadcastRequest;

class CreateHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = BroadcastResource::class;

	public static function getMethod()
	{
		return Handlers::POST;
	}

	public static function getModel()
	{
		return static::$resource::getModel();
	}

	/**
	 * Create Broadcast
	 *
	 * @param CreateBroadcastRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function handler(CreateBroadcastRequest $request)
	{
		$model = new (static::getModel());

		$model->fill($request->all());

		$model->user_id = $request->user_id ?? auth()->id();

		$model->save();

		return static::sendSuccessResponse($model, "Successfully Create Broadcast");
	}
}

