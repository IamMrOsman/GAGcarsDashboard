<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Requests\CreateItemRequest;
use App\Services\ApprovalRequirementService;
use App\Services\PaymentRequirementService;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ItemResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Item
     *
     * @param CreateItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateItemRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

		$model->user_id = $request->user_id ?? auth()->id();

        $model->save();

        //if approval is required before upload, set status to pending_approval
        $approvalRequirementService = new ApprovalRequirementService();
        $approvalCheck = $approvalRequirementService->checkApprovalRequirementForItem($model);
        if ($approvalCheck['require_approval']) {
            $model->update(['status' => 'pending_approval']);
        }

        //if payment is required before upload, decrement user's uploads_left
        $paymentRequirementService = new PaymentRequirementService();
        $paymentCheck = $paymentRequirementService->checkPaymentRequirementForItem($model);
        if ($paymentCheck['require_payment']) {
            $user = $model->user;
            $user->decrement('uploads_left', 1);
        }

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}
