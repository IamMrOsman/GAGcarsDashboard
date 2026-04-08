<?php

namespace App\Support;

/**
 * Single source of truth for configurable system event keys, labels, and template hints.
 */
final class EventMessageCatalog
{
	/**
	 * Event key => human label (email subject suffix / Filament dropdown).
	 *
	 * @var array<string, string>
	 */
	public const LABELS = [
		'new_account' => 'New Account Created',
		'account_verified' => 'Account Verified',
		'item_listed' => 'Item Listed',
		'item_submitted_for_approval' => 'Item Submitted for Approval',
		'item_sold' => 'Item Sold',
		'item_approved' => 'Item Approved',
		'item_rejected' => 'Item Rejected',
		'payment_successful' => 'Payment Successful',
		'payment_failed' => 'Payment Failed',
		'package_purchased' => 'Package Purchased',
		'upload_credits_added' => 'Upload Credits Added',
		'item_promoted' => 'Item Promoted',
		'promotion_expired' => 'Promotion Expired',
		'listing_expired' => 'Listing Expired',
		'wishlist_item_price_drop' => 'Wishlist Item Price Drop',
		'password_reset' => 'Password Reset',
		'otp_sent' => 'OTP Sent',
		'verification_approved' => 'Verification Approved',
		'verification_rejected' => 'Verification Rejected',
	];

	/**
	 * Optional per-event placeholder hints for admins (shown under the template field).
	 *
	 * @var array<string, string>
	 */
	public const TEMPLATE_HINTS = [
		'new_account' => '{user_name}, {email}, {phone}',
		'account_verified' => '{user_name}, {email}',
		'item_listed' => '{user_name}, {item_name}',
		'item_submitted_for_approval' => '{user_name}, {item_name}',
		'item_sold' => '{user_name}, {item_name}, {amount}',
		'item_approved' => '{user_name}, {item_name}',
		'item_rejected' => '{user_name}, {item_name}',
		'payment_successful' => '{user_name}, {amount} (and fields passed by Paystack/Wallet)',
		'payment_failed' => '{user_name} (and fields passed by Paystack)',
		'package_purchased' => '{user_name} (package fields from fulfillment)',
		'upload_credits_added' => '{user_name} (credit fields from fulfillment)',
		'item_promoted' => '{user_name}, {item_name} (promotion fields)',
		'promotion_expired' => '{user_name}, {item_name}',
		'listing_expired' => '{user_name}, {item_name}',
		'wishlist_item_price_drop' => '{user_name}, {item_name}, {old_price}, {new_price}, {amount}',
		'password_reset' => '{user_name}, {otp}, {email}, {phone}',
		'otp_sent' => '{user_name}, {otp}, {email}, {phone}',
		'verification_approved' => '{user_name}, {email}',
		'verification_rejected' => '{user_name}, {email}',
	];

	public static function hintFor(?string $eventKey): string
	{
		if ($eventKey === null || $eventKey === '') {
			return '';
		}

		return self::TEMPLATE_HINTS[$eventKey] ?? '';
	}
}
