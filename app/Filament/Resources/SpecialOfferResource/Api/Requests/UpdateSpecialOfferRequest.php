<?php

namespace App\Filament\Resources\SpecialOfferResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialOfferRequest extends FormRequest
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
			'item_id' => 'required',
			'start_at' => 'required',
			'end_at' => 'required',
			'status' => 'required',
			'discount' => 'required|integer',
			'discount_type' => 'required',
			'description' => 'required'
		];
    }
}
