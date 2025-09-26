<?php
namespace App\Filament\Resources\TransactionResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Transaction;

/**
 * @property Transaction $resource
 */
class TransactionTransformer extends JsonResource
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

		// Add any additional transformations here if needed

		return $data;
    }
}
