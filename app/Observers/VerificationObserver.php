<?php

namespace App\Observers;

use App\Models\Verification;
use App\Services\EventMessageService;

class VerificationObserver
{
	public function __construct(
		private readonly EventMessageService $eventMessages,
	) {}

	public function updated(Verification $verification): void
	{
		if (! $verification->wasChanged('status')) {
			return;
		}

		$user = $verification->relationLoaded('user') ? $verification->user : $verification->user()->first();
		if (! $user) {
			return;
		}

		if ($verification->status === 'verified') {
			$this->eventMessages->send('verification_approved', $user, []);
		}

		if ($verification->status === 'rejected') {
			$this->eventMessages->send('verification_rejected', $user, []);
		}
	}
}
