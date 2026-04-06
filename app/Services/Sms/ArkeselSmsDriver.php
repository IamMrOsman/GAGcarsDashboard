<?php

namespace App\Services\Sms;

use App\Services\SmsSettingsService;
use Illuminate\Support\Facades\Http;

class ArkeselSmsDriver
{
	public function send(string $to, string $message): bool
	{
		$data = SmsSettingsService::getSmsData();
		if ($data !== [] && ! SmsSettingsService::isSmsSendingEnabled()) {
			\Log::info('Arkesel SMS skipped: disabled or test mode in dashboard settings');

			return false;
		}

		$apiKey = SmsSettingsService::getArkeselApiKey() ?: config('services.arkesel.api_key');
		$sender = SmsSettingsService::getArkeselSenderId() ?: config('services.arkesel.sender_id');

		if (empty($apiKey) || empty($sender)) {
			\Log::warning('Arkesel SMS: missing api_key or sender_id (dashboard or config)');

			return false;
		}

		try {
			$response = Http::withHeaders([
				'api-key' => $apiKey,
				'Content-Type' => 'application/json',
			])->post('https://sms.arkesel.com/api/v2/sms/send', [
				'sender' => $sender,
				'message' => $message,
				'recipients' => [$to],
			]);

			return $response->successful();
		} catch (\Exception $e) {
			\Log::error('Arkesel SMS Error: '.$e->getMessage());

			return false;
		}
	}
}
