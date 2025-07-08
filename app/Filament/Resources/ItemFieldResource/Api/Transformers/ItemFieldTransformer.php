<?php
namespace App\Filament\Resources\ItemFieldResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ItemField;

/**
 * @property ItemField $resource
 */
class ItemFieldTransformer extends JsonResource
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
