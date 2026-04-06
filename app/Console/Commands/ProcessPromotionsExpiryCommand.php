<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use App\Services\EventMessageService;
use Illuminate\Console\Command;

class ProcessPromotionsExpiryCommand extends Command
{
	protected $signature = 'promotions:process-expiry';

	protected $description = 'Mark active promotions past end_at as expired and notify sellers';

	public function handle(EventMessageService $eventMessages): int
	{
		$count = 0;

		Promotion::query()
			->where('status', 'active')
			->whereNotNull('end_at')
			->where('end_at', '<', now())
			->with(['user', 'item'])
			->chunkById(100, function ($promotions) use ($eventMessages, &$count) {
				foreach ($promotions as $promotion) {
					$promotion->update(['status' => 'expired']);
					$count++;

					$user = $promotion->user;
					if ($user) {
						$item = $promotion->item;
						$endRaw = $promotion->end_at;
						$endStr = $endRaw
							? (string) (is_object($endRaw) && method_exists($endRaw, 'toIso8601String')
								? $endRaw->toIso8601String()
								: $endRaw)
							: '';
						$eventMessages->send('promotion_expired', $user, [
							'item_name' => $item ? (string) ($item->name ?? '') : '',
							'promotion_end_at' => $endStr,
						]);
					}
				}
			});

		$this->info("Processed {$count} expired promotion(s).");

		return self::SUCCESS;
	}
}
