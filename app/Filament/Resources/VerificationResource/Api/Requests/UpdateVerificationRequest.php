<?php

namespace App\Filament\Resources\VerificationResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationRequest extends FormRequest
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
			'document_type' => 'nullable',
			'document_number' => 'nullable',
			'document_front' => 'nullable',
			'document_back' => 'nullable',
			'selfie' => 'nullable',
			'status' => 'nullable',
			'comment' => 'nullable|string',
			'verified_by' => 'nullable',
			'rejected_by' => 'nullable',
			'approved_at' => 'nullable',
			'rejected_at' => 'nullable'
		];
    }
}
