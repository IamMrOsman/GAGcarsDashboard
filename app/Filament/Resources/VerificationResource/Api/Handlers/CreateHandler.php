<?php
namespace App\Filament\Resources\VerificationResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\VerificationResource;
use App\Filament\Resources\VerificationResource\Api\Requests\CreateVerificationRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = VerificationResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Verification
     *
     * @param CreateVerificationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateVerificationRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}