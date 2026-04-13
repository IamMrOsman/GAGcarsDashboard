<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\FcmService;

class PostObserver
{
	public function created(Post $post): void
	{
		// Push to a topic so clients can receive "new blog post" alerts.
		(new FcmService())->sendToTopic('blog', [
			'priority' => 'high',
			'notification' => [
				'title' => 'New blog post',
				'body' => (string) ($post->title ?? 'A new post is available'),
			],
			'data' => [
				'type' => 'blog_post',
				'post_id' => (string) $post->id,
				// Canonical: data.deeplink (mobile routes this). Keep deep_link for backward compatibility.
				'deeplink' => 'gagcars://blog?post=' . (string) $post->id,
				'deep_link' => 'gagcars://blog?post=' . (string) $post->id,
			],
		]);
	}
}

