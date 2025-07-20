<?php
namespace App\Filament\Resources\ItemResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Item;
use App\Filament\Resources\BrandResource\Api\Transformers\BrandTransformer;
use App\Filament\Resources\CategoryResource\Api\Transformers\CategoryTransformer;
use App\Filament\Resources\BrandModelResource\Api\Transformers\BrandModelTransformer;
use App\Filament\Resources\UserResource\Api\Transformers\UserTransformer;

/**
 * @property Item $resource
 */
class ItemTransformer extends JsonResource
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

		if ($this->resource->relationLoaded('brand') || $this->resource->brand) {
			$data['brand'] = new BrandTransformer($this->resource->brand);
		}

		if ($this->resource->relationLoaded('category') || $this->resource->category) {
			$data['category'] = new CategoryTransformer($this->resource->category);
		}

		if ($this->resource->relationLoaded('brandModel') || $this->resource->brandModel) {
			$data['brandModel'] = new BrandModelTransformer($this->resource->brandModel);
		}

		if ($this->resource->relationLoaded('user') || $this->resource->user) {
			$data['user'] = new UserTransformer($this->resource->user);
		}

		return $data;
    }
}
