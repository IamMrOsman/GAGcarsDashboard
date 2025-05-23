<?php

namespace App\Filament\Resources\BrandModelResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBrandModelRequest extends FormRequest
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
			'brand_id' => 'required|integer',
			'name' => 'required',
			'slug' => 'required'
		];
    }
}
