<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\ListingSettingsService;
use Illuminate\Console\Command;

class BackfillItemExpiresAtCommand extends Command
{
	protected $signature = 'items:backfill-expires-at';

	protected $description = 'Set expires_at for active items that are missing it (from now + configured active days)';

	public function handle(): int
	{
		$days = ListingSettingsService::getActiveListingDays();
		$expires = now()->addDays($days);

		$count = Item::query()
			->where('status', 'active')
			->whereNull('expires_at')
			->update(['expires_at' => $expires]);

		$this->info("Updated {$count} active item(s) with expires_at = {$expires->toDateTimeString()}.");

		return self::SUCCESS;
	}
}
