<?php

namespace App\Filament\Resources\BrandResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Brand;

/**
 * @property Brand $resource
 */
class BrandTransformer extends JsonResource
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
		$data['brand_model'] = $this->resource->brandModels;

		return $data;
	}
}
