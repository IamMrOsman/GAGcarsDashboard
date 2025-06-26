<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class ArkeselSmsDriver
{
	public function send(string $to, string $message): bool
	{
		try {
			$response = Http::withHeaders([
				'api-key' => config('services.arkesel.api_key'),
				'Content-Type' => 'application/json',
			])->post('https://sms.arkesel.com/api/v2/sms/send', [
				'sender' => config('services.arkesel.sender_id'),
				'message' => $message,
				'recipients' => [$to],
			]);

			return $response->successful();
		} catch (\Exception $e) {
			\Log::error('Arkesel SMS Error: ' . $e->getMessage());
			return false;
		}
	}
}
