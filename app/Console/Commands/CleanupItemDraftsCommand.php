<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;

class CleanupItemDraftsCommand extends Command
{
	protected $signature = 'drafts:cleanup {--days=7 : Delete drafts inactive for N days}';

	protected $description = 'Delete inactive item drafts (draft, pending_payment) older than N days.';

	public function handle(): int
	{
		$days = (int) $this->option('days');
		$days = $days > 0 ? $days : 7;

		$cutoff = now()->subDays($days);

		$q = Item::query()
			->whereIn('status', ['draft', 'pending_payment'])
			->where(function ($sub) use ($cutoff) {
				$sub->where('last_saved_at', '<', $cutoff)
					->orWhere(function ($sub2) use ($cutoff) {
						$sub2->whereNull('last_saved_at')
							->where('updated_at', '<', $cutoff);
					});
			});

		$count = (clone $q)->count();
		if ($count === 0) {
			$this->info('No inactive drafts to delete.');
			return self::SUCCESS;
		}

		$deleted = (clone $q)->delete();
		$this->info("Deleted {$deleted} inactive drafts (cutoff: {$cutoff->toDateTimeString()}).");

		return self::SUCCESS;
	}
}

