<?php
namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\FaqCategory;

/**
 * @property FaqCategory $resource
 */
class FaqCategoryTransformer extends JsonResource
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
