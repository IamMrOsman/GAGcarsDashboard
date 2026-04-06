<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\EventMessageService;
use Illuminate\Console\Command;

class ProcessItemsExpiryCommand extends Command
{
	protected $signature = 'items:process-expiry';

	protected $description = 'Mark active listings past expires_at as expired and notify sellers';

	public function handle(EventMessageService $eventMessages): int
	{
		$count = 0;

		Item::query()
			->where('status', 'active')
			->whereNotNull('expires_at')
			->where('expires_at', '<', now())
			->with('user')
			->chunkById(100, function ($items) use ($eventMessages, &$count) {
				foreach ($items as $item) {
					$item->update(['status' => 'expired']);
					$count++;

					$user = $item->user;
					if ($user) {
						$expiresRaw = $item->expires_at;
						$expiresStr = $expiresRaw
							? (string) (is_object($expiresRaw) && method_exists($expiresRaw, 'toIso8601String')
								? $expiresRaw->toIso8601String()
								: $expiresRaw)
							: '';
						$eventMessages->send('listing_expired', $user, [
							'item_name' => (string) ($item->name ?? ''),
							'expires_at' => $expiresStr,
						]);
					}
				}
			});

		$this->info("Processed {$count} expired listing(s).");

		return self::SUCCESS;
	}
}
