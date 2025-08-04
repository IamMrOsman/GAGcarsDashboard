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

		// Transform item_fields to use options_keys instead of options
		if (isset($data['item_fields']) && is_array($data['item_fields'])) {
			foreach ($data['item_fields'] as &$field) {
				if (isset($field['options_keys'])) {
					$field['options'] = $field['options_keys'];
					unset($field['options_keys']);
				}
			}
		}

		return $data;
	}
}
