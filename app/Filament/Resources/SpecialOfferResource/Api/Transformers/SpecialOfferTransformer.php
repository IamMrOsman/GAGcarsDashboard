<?php

namespace App\Filament\Resources\SpecialOfferResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SpecialOffer;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;

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
