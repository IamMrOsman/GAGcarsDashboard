<?php
namespace App\Filament\Resources\CountryResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryTransformer extends JsonResource
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
            'name' => $this->name,
            'iso3' => $this->iso3,
            'iso2' => $this->iso2,
            'numeric_code' => $this->numeric_code,
            'phone_code' => $this->phone_code,
            'capital' => $this->capital,
            'currency' => $this->currency,
            'currency_name' => $this->currency_name,
            'currency_symbol' => $this->currency_symbol,
            'tld' => $this->tld,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
