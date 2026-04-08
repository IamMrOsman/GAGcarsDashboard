<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Requests\CreateItemRequest;
use App\Models\CategoryRequirement;

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

        $payload = $request->all();

		// Mobile uploads send Cloudinary URLs in `images`.
		// Watermarking is enforced at upload time (Cloudinary transformation signed by backend),
		// so we keep URLs as-is to ensure fast delivery from Cloudinary.
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

		$model->user_id = $request->user_id ?? auth()->id();

		$model->country_id = $request->country_id ?? auth()->user()->country_id;

        $model->save();

        // Check if approval is required for this category in the user's country
        $approvalRequired = CategoryRequirement::where('category_id', $model->category_id)
            ->where('country_id', $model->user->country_id)
            ->where('require_approval', true)
            ->exists();

        // If approval is required, set status to pending_approval
        if ($approvalRequired) {
            $model->update(['status' => 'pending_approval']);
        } else {
            $model->update(['status' => 'active']);
        }

        // Check if payment is required for this category in the user's country
        $paymentRequired = CategoryRequirement::where('category_id', $model->category_id)
            ->where('country_id', $model->user->country_id)
            ->where('require_payment', true)
            ->exists();

        // If payment is required, decrement user's uploads for this category
        if ($paymentRequired) {
            $model->user->decrementUploadsForCategory($model->category_id);
        }

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}
