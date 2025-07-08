<?php

namespace App\Filament\Resources\VerificationResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVerificationRequest extends FormRequest
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
			'document_type' => 'required',
			'document_number' => 'required',
			'document_front' => 'required',
			'document_back' => 'required',
			'selfie' => 'required',
			'status' => 'required',
			'comment' => 'required|string',
			'verified_by' => 'required',
			'rejected_by' => 'required',
			'approved_at' => 'required',
			'rejected_at' => 'required'
		];
    }
}
