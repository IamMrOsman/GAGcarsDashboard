<?php
namespace App\Filament\Resources\TransactionResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Api\Requests\CreateTransactionRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TransactionResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Transaction
     *
     * @param CreateTransactionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTransactionRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

		$model->user_id = $request->user_id ?? auth()->id();

        $model->save();

        // Upload credits MUST NOT be granted here: Paystack verify/webhook and wallet purchase
        // already call PackageFulfillmentService::fulfillIfNeeded(). Granting again caused double
        // credits when the mobile app POSTed this row after a successful Paystack verification.

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}
