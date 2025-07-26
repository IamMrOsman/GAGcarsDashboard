<?php
namespace App\Filament\Resources\CategoryResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;

/**
 * @property Category $resource
 */
class CategoryTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
		$data = $this->resource->toArray();
		$data['fields'] = $this->resource->itemFields;

        return $data;
    }
}
