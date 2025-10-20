<?php

namespace App\Filament\Resources\BroadcastResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BroadcastTransformer extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
	 */
	public function toArray($request)
	{
		return [
			'id' => $this->id,
			'user_id' => $this->user_id,
			'country_id' => $this->country_id,
			'subject' => $this->subject,
			'message' => $this->message,
			'image' => $this->image,
			'status' => $this->status,
			'scheduled_at' => $this->scheduled_at,
			'target' => $this->target,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
			'user' => $this->whenLoaded('user', function () {
				return [
					'id' => $this->user->id,
					'name' => $this->user->name,
					'email' => $this->user->email,
				];
			}),
			'country' => $this->whenLoaded('country', function () {
				return [
					'id' => $this->country->id,
					'name' => $this->country->name,
				];
			}),
		];
	}
}

