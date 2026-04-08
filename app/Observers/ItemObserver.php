<?php

namespace App\Observers;

use App\Models\Item;
use App\Models\User;
use App\Models\WishList;
use App\Services\EventMessageService;
use App\Services\ListingSettingsService;
use Illuminate\Support\Facades\DB;

class ItemObserver
{
	public function __construct(
		private readonly EventMessageService $eventMessages,
	) {}

	public function created(Item $item): void
	{
		if ($item->status === 'active') {
			$this->applyActiveListingExpiry($item);
		}
	}

	public function updated(Item $item): void
	{
		if ($item->wasChanged('status')) {
			$this->handleStatusChange($item);
		}

		if ($item->wasChanged('price') && $item->status === 'active') {
			$this->maybeNotifyWishlistPriceDrop($item);
		}
	}

	private function handleStatusChange(Item $item): void
	{
		$old = $item->getOriginal('status');
		$new = $item->status;

		if ($new === 'active' && $old !== 'active') {
			$this->applyActiveListingExpiry($item);
		}

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

	private function maybeNotifyWishlistPriceDrop(Item $item): void
	{
		$oldRaw = $item->getOriginal('price');
		$newRaw = $item->price;
		$old = $this->parsePriceToInt($oldRaw);
		$new = $this->parsePriceToInt($newRaw);
		if ($old === null || $new === null || $new >= $old) {
			return;
		}

		$sellerId = (string) $item->user_id;

		WishList::query()
			->where('item_id', $item->id)
			->where('user_id', '!=', $sellerId)
			->get()
			->unique('user_id')
			->each(function (WishList $row) use ($item, $old, $new): void {
				$u = User::find($row->user_id);
				if (! $u) {
					return;
				}
				$this->eventMessages->send('wishlist_item_price_drop', $u, [
					'item_name' => (string) ($item->name ?? ''),
					'old_price' => (string) $old,
					'new_price' => (string) $new,
					'amount' => (string) $new,
				]);
			});
	}

	private function parsePriceToInt(mixed $price): ?int
	{
		if ($price === null || $price === '') {
			return null;
		}
		$n = preg_replace('/[^\d]/', '', (string) $price);

		return $n === '' ? null : (int) $n;
	}

	/**
	 * Persist expiry without firing model events (query builder update).
	 */
	private function applyActiveListingExpiry(Item $item): void
	{
		$days = ListingSettingsService::getActiveListingDays();
		DB::table('items')->where('id', $item->getKey())->update([
			'expires_at' => now()->addDays($days),
		]);
	}
}
