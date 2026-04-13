<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use App\Models\CategoryRequirement;
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

        $user = $request->user();
        if (! $user || (string) $model->user_id !== (string) $user->id) {
            return response()->json([
                'message' => 'You do not have permission to update this listing.',
            ], 403);
        }

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

        // Never trust the client to set status (prevents skipping payment / relist rules).
        unset($payload['status']);

        $wasExpired = $model->status === 'expired';
        $newStatus = null;

        if ($wasExpired) {
            $categoryId = $payload['category_id'] ?? $model->category_id;
            if (! $categoryId) {
                return response()->json([
                    'message' => 'category_id is required to relist an expired listing.',
                ], 422);
            }

            $paymentRequired = CategoryRequirement::where('category_id', $categoryId)
                ->where('country_id', $user->country_id)
                ->where('require_payment', true)
                ->exists();

            if ($paymentRequired) {
                $uploadsLeft = $user->getUploadsLeftForCategory($categoryId);
                if ($uploadsLeft <= 0) {
                    return response()->json([
                        'message' => 'No uploads left for this category. Please purchase an upload package.',
                    ], 402);
                }
                $user->decrementUploadsForCategory($categoryId);
            }

            $approvalRequired = CategoryRequirement::where('category_id', $categoryId)
                ->where('country_id', $user->country_id)
                ->where('require_approval', true)
                ->exists();

            $newStatus = $approvalRequired ? 'pending_approval' : 'active';
        }

        $model->fill($payload);

        if ($wasExpired && $newStatus !== null) {
            $model->status = $newStatus;
        }

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}