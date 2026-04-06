<?php

namespace App\Observers;

use App\Models\Item;
use App\Models\User;
use App\Services\EventMessageService;

class ItemObserver
{
	public function __construct(
		private readonly EventMessageService $eventMessages,
	) {}

	public function updated(Item $item): void
	{
		if (! $item->wasChanged('status')) {
			return;
		}

		$old = $item->getOriginal('status');
		$new = $item->status;

		$user = $item->relationLoaded('user') ? $item->user : User::find($item->user_id);
		if (! $user) {
			return;
		}

		$ctx = [
			'item_name' => (string) ($item->name ?? ''),
		];

		if ($new === 'active' && $old === 'pending_approval') {
			$this->eventMessages->send('item_approved', $user, $ctx);
		}

		if ($new === 'rejected' && $old !== 'rejected') {
			$this->eventMessages->send('item_rejected', $user, $ctx);
		}

		if ($new === 'sold' && $old !== 'sold') {
			$this->eventMessages->send('item_sold', $user, array_merge($ctx, [
				'amount' => (string) ($item->price ?? ''),
			]));
		}
	}
}
