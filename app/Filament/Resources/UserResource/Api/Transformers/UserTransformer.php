<?php

namespace App\Filament\Resources\UserResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

/**
 * @property User $resource
 */
class UserTransformer extends JsonResource
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

		$data['is_verified'] = $this->resource->isVerified();
		$data['is_verified_dealer'] = $this->resource->isVerifiedDealer();
		$data['ads'] = $this->resource->items()->count();

		return $data;
	}
}
