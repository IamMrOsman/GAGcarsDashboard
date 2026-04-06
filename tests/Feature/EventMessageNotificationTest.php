<?php

use App\Mail\EventMessageMail;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use App\Services\EventMessageService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

test('interpolate replaces placeholders', function () {
	expect(EventMessageService::interpolateTemplate('Hello {user_name}', ['user_name' => 'Ada']))->toBe('Hello Ada');
});

test('send emails when new_account template enabled', function () {
	Mail::fake();

	Setting::create([
		'key_name' => 'Event Messages Configuration',
		'key_slug' => 'event_messages',
		'value' => 'configured',
		'data' => [
			'event_messages' => [
				[
					'event' => 'new_account',
					'channel' => 'email',
					'message' => 'Welcome {user_name}',
					'enabled' => true,
				],
			],
		],
	]);

	$user = User::factory()->create([
		'name' => 'Tester',
		'email' => 'tester@example.com',
	]);

	app(EventMessageService::class)->send('new_account', $user, []);

	Mail::assertSent(EventMessageMail::class, function (EventMessageMail $mail) {
		return str_contains($mail->bodyText, 'Welcome Tester');
	});
});

test('send skips when template disabled', function () {
	Mail::fake();

	Setting::create([
		'key_name' => 'Event Messages Configuration',
		'key_slug' => 'event_messages',
		'value' => 'configured',
		'data' => [
			'event_messages' => [
				[
					'event' => 'new_account',
					'channel' => 'email',
					'message' => 'Welcome',
					'enabled' => false,
				],
			],
		],
	]);

	$user = User::factory()->create();

	app(EventMessageService::class)->send('new_account', $user, []);

	Mail::assertNothingSent();
});

test('items process expiry marks item expired', function () {
	Mail::fake();

	$user = User::factory()->create();

	$item = Item::query()->create([
		'user_id' => $user->id,
		'name' => 'Test Car',
		'slug' => 'test-car-'.uniqid('', true),
		'status' => 'active',
		'expires_at' => now()->subDay(),
	]);

	Artisan::call('items:process-expiry');

	$item->refresh();

	expect($item->status)->toBe('expired');
});
