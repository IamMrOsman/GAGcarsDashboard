<?php

namespace App\Services;

use App\Models\DeleteAccountRequest;
use App\Models\DeletedUserArchive;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeleteAccountService
{
	/**
	 * Build a snapshot of user data for archival/audit.
	 *
	 * @return array<string, mixed>
	 */
	public function buildSnapshot(User $user): array
	{
		try {
			$user->loadMissing('roles');
		} catch (\Throwable) {
			// optional
		}

		$userId = (string) $user->getKey();

		$listingsTotal = Item::query()
			->where('user_id', $userId)
			->count();
		$listingsActive = Item::query()
			->where('user_id', $userId)
			->where('status', 'active')
			->count();
		$listingsExpired = Item::query()
			->where('user_id', $userId)
			->where('status', 'expired')
			->count();
		$listingsSold = Item::query()
			->where('user_id', $userId)
			->where('status', 'sold')
			->count();

		$walletBalance = WalletBalance::query()
			->where('user_id', $userId)
			->value('balance');
		$walletBalance = $walletBalance === null ? 0 : (float) $walletBalance;

		$totalTransactions = Transaction::query()
			->where('user_id', $userId)
			->count();

		$totalWalletTopups = WalletLedger::query()
			->where('user_id', $userId)
			->where('reason', 'wallet_topup')
			->count();

		return [
			'user' => $user->toArray(),
			'summary' => [
				'profile_photo' => (string) ($user->profile_photo ?? ''),
				'uploads_left' => $user->uploads_left ?? [],
				'listings' => [
					'total' => $listingsTotal,
					'active' => $listingsActive,
					'expired' => $listingsExpired,
					'sold' => $listingsSold,
				],
				'wallet' => [
					'balance' => $walletBalance,
				],
				'transactions' => [
					'total' => $totalTransactions,
					'wallet_topups' => $totalWalletTopups,
				],
			],
			'meta' => [
				'submitted_at' => now()->toISOString(),
			],
		];
	}

	public function sendSubmittedNotifications(User $user): void
	{
		$this->sendUserEmail($user, 'Delete account request received', 'We received your account deletion request. Your account is temporarily restricted while we review it.');
		$this->sendUserSms($user, 'GAGcars: Your account deletion request was received. Your account is temporarily restricted while we review it.');
	}

	public function sendApprovedNotifications(User $user): void
	{
		$this->sendUserEmail($user, 'Delete account request approved', 'Your account deletion request has been approved. You have been logged out and your account is no longer accessible.');
		$this->sendUserSms($user, 'GAGcars: Your account deletion request has been approved. You have been logged out and your account is no longer accessible.');
	}

	/**
	 * Approve a delete account request: archive, remove from app, and notify.
	 */
	public function approve(DeleteAccountRequest $request, User $admin): void
	{
		$user = User::withTrashed()->findOrFail($request->user_id);

		// Notify first while contact details still exist.
		$this->sendApprovedNotifications($user);

		$payload = [
			'request' => $request->toArray(),
			'snapshot' => $request->snapshot,
			'archived_at' => now()->toISOString(),
		];

		DeletedUserArchive::create([
			'original_user_id' => $user->id,
			'payload' => $payload,
			'archived_at' => now(),
		]);

		// Remove user-generated content from app (keep in DB).
		Item::where('user_id', $user->id)->delete();

		// Revoke tokens/sessions
		try {
			$user->tokens()->delete();
		} catch (\Throwable) {}

		// Anonymize PII but keep row for FK integrity.
		$user->forceFill([
			'name' => 'Deleted User',
			'email' => 'deleted+'.$user->id.'@example.invalid',
			'phone' => null,
			'profile_photo' => null,
		])->save();

		$user->delete(); // soft delete

		$request->forceFill([
			'status' => 'approved',
			'reviewed_at' => now(),
			'reviewed_by' => $admin->id,
		])->save();
	}

	public function reject(DeleteAccountRequest $request, User $admin, ?string $reason = null): void
	{
		$request->forceFill([
			'status' => 'rejected',
			'reviewed_at' => now(),
			'reviewed_by' => $admin->id,
			'reason' => $reason,
		])->save();
	}

	private function sendUserEmail(User $user, string $subject, string $body): void
	{
		$to = (string) ($user->email ?? '');
		if ($to === '') return;

		try {
			if (SmtpSettingsService::isSmtpConfigured()) {
				$smtpConfig = SmtpSettingsService::getSmtpConfig();
				Config::set('mail.mailers.smtp', $smtpConfig['mailers']['smtp']);
				Config::set('mail.from', $smtpConfig['from']);
			}
			Mail::raw($body, function ($message) use ($to, $subject) {
				$message->to($to)->subject($subject);
			});
		} catch (\Throwable $e) {
			Log::warning('DeleteAccountService: email send failed', ['error' => $e->getMessage()]);
		}
	}

	private function sendUserSms(User $user, string $body): void
	{
		$phone = (string) ($user->phone ?? '');
		if ($phone === '') return;

		try {
			$driver = new \App\Services\Sms\ArkeselSmsDriver();
			$driver->send($phone, $body);
		} catch (\Throwable $e) {
			Log::warning('DeleteAccountService: sms send failed', ['error' => $e->getMessage()]);
		}
	}
}

