<?php

namespace App\Filament\Resources\PromotionResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
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
			'item_id' => 'required',
			'start_at' => 'required',
			'end_at' => 'required',
			'status' => 'required'
		];
    }
}
