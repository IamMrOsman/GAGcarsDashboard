<?php

namespace App\Filament\Resources\BroadcastResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBroadcastRequest extends FormRequest
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
			'country_id' => 'nullable|exists:countries,id',
			'subject' => 'sometimes|required|string|max:255',
			'message' => 'sometimes|required|string',
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
			'status' => 'nullable|in:draft,scheduled,sent',
			'scheduled_at' => 'nullable|date',
			'target' => 'nullable|in:customers,dealers,all'
		];
	}
}

