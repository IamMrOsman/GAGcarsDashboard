<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Requests\UpdateItemRequest;
use App\Services\WatermarkService;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = ItemResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update Item
     *
     * @param UpdateItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateItemRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $payload = $request->all();

		// If mobile sends new remote URLs in images, download + watermark + store locally.
		if (isset($payload['images']) && is_array($payload['images'])) {
			$out = [];
			foreach ($payload['images'] as $img) {
				$s = is_string($img) ? trim($img) : '';
				if ($s === '') continue;

				if (str_starts_with($s, 'http')) {
					$stored = WatermarkService::watermarkRemoteUrlToPublic($s, 'items');
					// Always keep something so the item still has images.
					$out[] = (is_string($stored) && $stored !== '') ? $stored : $s;
				} else {
					$out[] = $s;
				}
			}
			$payload['images'] = $out;
		}

        $model->fill($payload);

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}