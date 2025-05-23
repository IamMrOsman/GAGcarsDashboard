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
			'user_id' => 'required',
			'brand_model_id' => 'required|integer',
			'brand_id' => 'required|integer',
			'category_id' => 'required|integer',
			'name' => 'required',
			'slug' => 'required',
			'description' => 'required',
			'images' => 'required|string',
			'location' => 'required',
			'serial_number' => 'required',
			'condition' => 'required',
			'status' => 'required',
			'price' => 'required',
			'mileage' => 'required',
			'warranty' => 'required',
			'warranty_expiration' => 'required',
			'deleted_at' => 'required'
		];
    }
}
