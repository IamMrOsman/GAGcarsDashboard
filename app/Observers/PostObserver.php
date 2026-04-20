<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\FcmService;

class PostObserver
{
	public function created(Post $post): void
	{
		if (! $this->isPublished($post->status)) {
			return;
		}

		$this->pushNewBlogPostNotification($post);
	}

	public function updated(Post $post): void
	{
		if (! $post->wasChanged('status')) {
			return;
		}

		if (! $this->isPublished($post->status)) {
			return;
		}

		// Only when moving into published (e.g. draft → published). Avoid repeats on edits.
		if ($this->isPublished($post->getOriginal('status'))) {
			return;
		}

		$this->pushNewBlogPostNotification($post);
	}

	private function isPublished(mixed $status): bool
	{
		return strtolower(trim((string) $status)) === 'published';
	}

	private function pushNewBlogPostNotification(Post $post): void
	{
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
