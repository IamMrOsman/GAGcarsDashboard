<?php

namespace App\Filament\Resources\CategoryResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
			'parent_id' => 'required|integer',
			'name' => 'required',
			'slug' => 'required',
			'description' => 'required',
			'image' => 'required'
		];
    }
}
