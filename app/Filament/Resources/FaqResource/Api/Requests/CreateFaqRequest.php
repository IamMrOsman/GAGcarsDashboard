<?php

namespace App\Filament\Resources\FaqResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFaqRequest extends FormRequest
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
			'question' => 'required',
			'answer' => 'required|string',
			'status' => 'required',
			'tags' => 'required'
		];
    }
}
