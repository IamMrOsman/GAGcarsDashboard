<?php

namespace App\Filament\Resources\ItemResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'user_id' => 'nullable',
			'brand_model_id' => 'nullable|integer',
			'brand_id' => 'nullable|integer',
			'category_id' => 'nullable|integer',
			'name' => 'nullable',
			'slug' => 'nullable',
			'description' => 'nullable',
			'images' => 'nullable|string',
			'location' => 'nullable',
			'serial_number' => 'nullable',
			'condition' => 'nullable',
			'status' => 'nullable',
			'price' => 'nullable',
			'mileage' => 'nullable',
			'warranty' => 'nullable',
			'warranty_expiration' => 'nullable',
			'deleted_at' => 'nullable'
		];
    }
}
