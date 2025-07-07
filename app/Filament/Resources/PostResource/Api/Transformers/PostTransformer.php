<?php
namespace App\Filament\Resources\PostResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Post;

/**
 * @property Post $resource
 */
class PostTransformer extends JsonResource
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
