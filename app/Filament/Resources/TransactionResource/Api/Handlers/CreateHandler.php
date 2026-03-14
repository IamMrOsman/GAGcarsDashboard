<?php
namespace App\Filament\Resources\TransactionResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Api\Requests\CreateTransactionRequest;
use App\Models\User;
use App\Models\Package;

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

        // If the package type is 'upload', add uploads for the package's category (or 'all' if no category)
        if ($model->package && $model->package->package_type === 'upload') {
            $user = $model->user;
            if ($user) {
                $amount = (int) $model->package->number_of_listings;
                $categoryId = $model->package->category_id;
                $user->addUploadsForCategory($categoryId, $amount);
            }
        }

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}
