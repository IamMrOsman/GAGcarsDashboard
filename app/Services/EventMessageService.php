<?php

namespace App\Services;

use App\Mail\EventMessageMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\EventMessageCatalog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EventMessageService
{
	/**
	 * Send configured event message (email and/or SMS). Safe to call from HTTP flows: failures are logged.
	 *
	 * @param  array<string, string|int|float|null>  $context  Placeholders e.g. user_name, item_name, amount
	 */
	public function send(string $eventKey, ?User $user, array $context = []): void
	{
		try {
			$row = $this->findEnabledRow($eventKey);
			if ($row === null) {
				return;
			}

			$merged = $this->mergeUserContext($user, $context);
			$body = $this->interpolate((string) $row['message'], $merged);
			$channel = (string) ($row['channel'] ?? 'email');

			if ($channel === 'email' || $channel === 'both') {
				$this->sendEmail($user, $eventKey, $body, $merged);
			}

			if ($channel === 'sms' || $channel === 'both') {
				$this->sendSms($user, $body);
			}
		} catch (\Throwable $e) {
			Log::error('EventMessageService: failed to send', [
				'event' => $eventKey,
				'error' => $e->getMessage(),
			]);
		}
	}

	/**
	 * Whether an enabled template would send SMS for this event (avoids duplicating hardcoded OTP SMS).
	 */
	public function willSendSms(string $eventKey): bool
	{
		$row = $this->findEnabledRow($eventKey);
		if ($row === null) {
			return false;
		}
		$channel = (string) ($row['channel'] ?? 'email');

		return $channel === 'sms' || $channel === 'both';
	}

	/**
	 * Whether an enabled template would send email for this event (avoids duplicating OtpMail).
	 */
	public function willSendEmail(string $eventKey): bool
	{
		$row = $this->findEnabledRow($eventKey);
		if ($row === null) {
			return false;
		}
		$channel = (string) ($row['channel'] ?? 'email');

		return $channel === 'email' || $channel === 'both';
	}

	/**
	 * @param  array<string, string|int|float|null>  $context
	 */
	public static function interpolateTemplate(string $template, array $context): string
	{
		return preg_replace_callback('/\{([^}]+)\}/', function (array $m) use ($context): string {
			$key = $m[1];
			if (! array_key_exists($key, $context)) {
				return $m[0];
			}
			$val = $context[$key];

			return $val === null ? '' : (string) $val;
		}, $template) ?? $template;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function findEnabledRow(string $eventKey): ?array
	{
		$setting = Setting::where('key_slug', 'event_messages')->first();
		if (! $setting || ! is_array($setting->data)) {
			return null;
		}

		$rows = $setting->data['event_messages'] ?? null;
		if (! is_array($rows)) {
			return null;
		}

		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			if (($row['event'] ?? null) !== $eventKey) {
				continue;
			}
			$enabled = $row['enabled'] ?? true;
			if ($enabled === false || $enabled === '0' || $enabled === 'false') {
				continue;
			}

			return $row;
		}

		return null;
	}

	/**
	 * @param  array<string, string|int|float|null>  $context
	 * @return array<string, string>
	 */
	private function mergeUserContext(?User $user, array $context): array
	{
		$base = [];
		foreach ($context as $k => $v) {
			$base[(string) $k] = $v === null ? '' : (string) $v;
		}

		if ($user !== null) {
			$base['user_name'] = $base['user_name'] ?? (string) $user->name;
			$base['email'] = $base['email'] ?? (string) ($user->email ?? '');
			$base['phone'] = $base['phone'] ?? (string) ($user->phone ?? '');
		}

		return $base;
	}

	/**
	 * @param  array<string, string>  $merged
	 */
	private function interpolate(string $template, array $merged): string
	{
		return self::interpolateTemplate($template, $merged);
	}

	/**
	 * @param  array<string, string>  $merged
	 */
	private function sendEmail(?User $user, string $eventKey, string $body, array $merged): void
	{
		$to = $merged['email'] ?? '';
		if ($to === '' && $user !== null) {
			$to = (string) ($user->email ?? '');
		}
		if ($to === '') {
			Log::info('EventMessageService: skip email (no address)', ['event' => $eventKey]);

			return;
		}

		$label = EventMessageCatalog::LABELS[$eventKey] ?? $eventKey;
		$subject = 'GAGcars — '.$label;

		if (SmtpSettingsService::isSmtpConfigured()) {
			$smtpConfig = SmtpSettingsService::getSmtpConfig();
			Config::set('mail.mailers.smtp', $smtpConfig['mailers']['smtp']);
			Config::set('mail.from', $smtpConfig['from']);
		}

		Mail::to($to)->send(new EventMessageMail($subject, $body));
	}

	private function sendSms(?User $user, string $body): void
	{
		$phone = $user !== null ? (string) ($user->phone ?? '') : '';
		if ($phone === '') {
			Log::info('EventMessageService: skip SMS (no phone)');

			return;
		}

		$driver = new \App\Services\Sms\ArkeselSmsDriver();
		$driver->send($phone, $body);
	}
}
