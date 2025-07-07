<?php
namespace App\Filament\Resources\FaqResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Faq;

/**
 * @property Faq $resource
 */
class FaqTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
