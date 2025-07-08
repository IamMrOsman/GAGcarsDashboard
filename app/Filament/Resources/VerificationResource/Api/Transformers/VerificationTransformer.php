<?php
namespace App\Filament\Resources\VerificationResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Verification;

/**
 * @property Verification $resource
 */
class VerificationTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
