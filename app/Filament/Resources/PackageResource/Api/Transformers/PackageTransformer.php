<?php
namespace App\Filament\Resources\PackageResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Package;

/**
 * @property Package $resource
 */
class PackageTransformer extends JsonResource
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
