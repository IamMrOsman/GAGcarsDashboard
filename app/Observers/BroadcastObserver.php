<?php

namespace App\Observers;

use App\Models\Broadcast;
use App\Services\FcmService;

class BroadcastObserver
{
	public function created(Broadcast $broadcast): void
	{
		(new FcmService())->sendToTopic('broadcasts', [
			'priority' => 'high',
			'notification' => [
				'title' => (string) ($broadcast->subject ?? 'Broadcast'),
				'body' => (string) ($broadcast->message ?? ''),
			],
			'data' => [
				'type' => 'broadcast',
				'broadcast_id' => (string) $broadcast->id,
				// Canonical: data.deeplink (mobile routes this). Keep deep_link for backward compatibility.
				'deeplink' => 'gagcars://broadcast?id=' . (string) $broadcast->id,
				'deep_link' => 'gagcars://broadcast?id=' . (string) $broadcast->id,
			],
		]);
	}
}

