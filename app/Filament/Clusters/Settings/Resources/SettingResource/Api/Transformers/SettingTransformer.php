<?php

namespace App\Filament\Clusters\Settings\Resources\SettingResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Setting;

/**
 * @property Setting $resource
 */
class SettingTransformer extends JsonResource
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

		return $data;
	}
}
