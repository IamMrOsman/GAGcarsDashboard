<?php

namespace App\Filament\Resources\TransactionResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
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
			'package_id' => 'required|exists:packages,id',
			'item_id' => 'nullable|exists:items,id',
			'amount' => 'required|numeric',
			'payment_channel' => 'required|string',
			'status' => 'required|string'
		];
    }
}
