<?php
namespace App\Filament\Resources\BrandModelResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\BrandModel;

/**
 * @property BrandModel $resource
 */
class BrandModelTransformer extends JsonResource
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
