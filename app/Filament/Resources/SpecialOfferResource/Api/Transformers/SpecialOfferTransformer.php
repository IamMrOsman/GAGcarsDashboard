<?php

namespace App\Filament\Resources\SpecialOfferResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SpecialOffer;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;
use App\Filament\Resources\BrandResource\Api\Transformers\BrandTransformer;
use App\Filament\Resources\CategoryResource\Api\Transformers\CategoryTransformer;
use App\Filament\Resources\BrandModelResource\Api\Transformers\BrandModelTransformer;
use App\Filament\Resources\UserResource\Api\Transformers\UserTransformer;

/**
 * @property SpecialOffer $resource
 */
class SpecialOfferTransformer extends JsonResource
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

		// Include the related item object
		if ($this->resource->relationLoaded('item') || $this->resource->item) {
			$data['item'] = new ItemTransformer($this->resource->item);
		}

		return $data;
	}
}
