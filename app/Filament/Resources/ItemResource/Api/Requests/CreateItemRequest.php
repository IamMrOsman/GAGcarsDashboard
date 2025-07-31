<?php

namespace App\Filament\Resources\ItemResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateItemRequest extends FormRequest
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
			'user_id' => '',
			'brand_model_id' => 'nullable',
			'brand_id' => 'nullable',
			'category_id' => 'nullable',
			'name' => 'required',
			'slug' => 'required',
			'description' => 'nullable',
			'images' => 'required|string',
			'location' => 'required',
			'serial_number' => 'nullable',
			'condition' => 'nullable',
			'status' => 'nullable',
			'price' => 'nullable',
			'mileage' => 'nullable',
			'warranty' => 'nullable',
			'warranty_expiration' => 'nullable'
		];
    }
}
