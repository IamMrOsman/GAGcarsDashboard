<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Requests\UpdateItemRequest;

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

		// Mobile sends Cloudinary URLs in images. Watermarking is enforced at upload time,
		// so keep URLs as-is to ensure fast delivery from Cloudinary.
		if (isset($payload['images']) && is_array($payload['images'])) {
			$out = [];
			foreach ($payload['images'] as $img) {
				$s = is_string($img) ? trim($img) : '';
				if ($s === '') continue;
				$out[] = $s;
			}
			$payload['images'] = $out;
		}

        $model->fill($payload);

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}