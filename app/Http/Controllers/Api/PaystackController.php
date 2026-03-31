<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\Transaction;
use App\Services\PaystackService;
use App\Services\PaystackSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaystackController extends Controller
{
	public function __construct(
		private readonly PaystackService $paystack,
	) {}

	/**
	 * Start a Paystack payment for a package.
	 * Flutter should open the returned authorization_url.
	 */
	public function initialize(Request $request)
	{
		if (!PaystackSettingsService::isPaystackConfigured()) {
			return response()->json([
				'success' => false,
				'message' => 'Paystack is not configured',
			], 503);
		}

		$data = $request->validate([
			'package_id' => ['required', 'string', 'exists:packages,id'],
			'item_id' => ['nullable', 'string', 'exists:items,id'],
			'email' => ['nullable', 'email'],
			'callback_url' => ['nullable', 'url'],
		]);

		$user = $request->user();
		$package = Package::with('country')->findOrFail($data['package_id']);

		// Packages are country-specific in your UI/API.
		if ($package->country_id !== null && $user->country_id !== null && $package->country_id !== $user->country_id) {
			throw ValidationException::withMessages([
				'package_id' => ['Package is not available for your country.'],
			]);
		}

		$item = null;
		if ($package->package_type === 'promotion') {
			if (empty($data['item_id'])) {
				throw ValidationException::withMessages([
					'item_id' => ['item_id is required for promotion packages.'],
				]);
			}

			$item = Item::findOrFail($data['item_id']);
			if ((string) $item->user_id !== (string) $user->id) {
				throw ValidationException::withMessages([
					'item_id' => ['You can only promote your own item.'],
				]);
			}
		}

		$reference = $this->paystack->generateReference('gag');
		$amount = (float) $package->price;
		$amountInKobo = (int) round($amount * 100);

		$email = $data['email'] ?? $user->email;
		if (!is_string($email) || trim($email) === '') {
			return response()->json([
				'success' => false,
				'message' => 'A valid email is required for payment. Please update your profile.',
			], 422);
		}

		$transaction = Transaction::create([
			'user_id' => $user->id,
			'package_id' => $package->id,
			'item_id' => $item?->id,
			'amount' => $amount,
			'payment_channel' => 'paystack',
			'status' => 'initialized',
			'reference' => $reference,
			'gateway' => 'paystack',
			'metadata' => [
				'package_type' => $package->package_type,
			],
		]);

		$paystackMetadata = [
			'transaction_id' => (string) $transaction->id,
			'user_id' => (string) $user->id,
			'package_id' => (string) $package->id,
			'package_type' => (string) $package->package_type,
		];
		if ($item !== null) {
			$paystackMetadata['item_id'] = (string) $item->id;
		}

		$payload = [
			'email' => trim($email),
			'amount' => $amountInKobo,
			'reference' => $reference,
			'metadata' => $paystackMetadata,
		];

		if (!empty($data['callback_url'])) {
			$payload['callback_url'] = $data['callback_url'];
		} else {
			$defaultCallback = (string) PaystackSettingsService::getSetting('paystack_callback_url', '');
			if ($defaultCallback !== '') {
				$payload['callback_url'] = $defaultCallback;
			}
		}

		try {
			$res = $this->paystack->initializeTransaction($payload);
		} catch (RequestException $e) {
			$body = $e->response?->json();
			$message = is_array($body) && isset($body['message']) && is_string($body['message'])
				? $body['message']
				: 'Payment gateway error. Please try again or contact support.';
			$transaction->update([
				'status' => 'gateway_error',
				'gateway_response' => is_array($body) ? $body : ['error' => $e->getMessage()],
			]);

			return response()->json([
				'success' => false,
				'message' => $message,
			], 422);
		}

		$transaction->update([
			'gateway_response' => $res,
		]);

		return response()->json([
			'success' => true,
			'message' => 'Payment initialized',
			'data' => [
				'reference' => $reference,
				'authorization_url' => data_get($res, 'data.authorization_url'),
				'access_code' => data_get($res, 'data.access_code'),
				'transaction' => $transaction,
			],
		], 200);
	}

	/**
	 * Public-safe Paystack config for frontend clients.
	 */
	public function config(Request $request)
	{
		return response()->json([
			'success' => true,
			'message' => 'Paystack config fetched',
			'data' => PaystackSettingsService::getPublicConfig(),
		], 200);
	}

	/**
	 * Verify a Paystack transaction by reference.
	 * Flutter should call this after the user completes payment.
	 */
	public function verify(Request $request)
	{
		if (!PaystackSettingsService::isPaystackConfigured()) {
			return response()->json([
				'success' => false,
				'message' => 'Paystack is not configured',
			], 503);
		}

		$data = $request->validate([
			'reference' => ['required', 'string'],
		]);

		$user = $request->user();
		$transaction = Transaction::with(['package'])->where('reference', $data['reference'])->firstOrFail();

		if ((string) $transaction->user_id !== (string) $user->id) {
			return response()->json([
				'success' => false,
				'message' => 'Forbidden',
			], 403);
		}

		$res = $this->paystack->verifyTransaction($data['reference']);
		$paystackStatus = (string) data_get($res, 'data.status');
		$paidAmount = (int) data_get($res, 'data.amount', 0); // kobo
		$currency = (string) data_get($res, 'data.currency');
		$paidAt = data_get($res, 'data.paid_at');
		$gatewayTxnId = data_get($res, 'data.id');

		$expectedAmount = (int) round(((float) $transaction->amount) * 100);
		if ($expectedAmount > 0 && $paidAmount > 0 && $expectedAmount !== $paidAmount) {
			$transaction->update([
				'status' => 'amount_mismatch',
				'gateway_response' => $res,
				'currency' => $currency ?: $transaction->currency,
				'gateway_transaction_id' => $gatewayTxnId ? (string) $gatewayTxnId : $transaction->gateway_transaction_id,
			]);

			return response()->json([
				'success' => false,
				'message' => 'Payment amount mismatch',
			], 409);
		}

		$success = $paystackStatus === 'success';
		$transaction->update([
			'status' => $success ? 'success' : ($paystackStatus ?: 'failed'),
			'gateway_response' => $res,
			'currency' => $currency ?: $transaction->currency,
			'gateway_transaction_id' => $gatewayTxnId ? (string) $gatewayTxnId : $transaction->gateway_transaction_id,
			'paid_at' => $success && $paidAt ? Carbon::parse($paidAt) : $transaction->paid_at,
		]);

		if ($success) {
			$this->fulfillIfNeeded($transaction);
		}

		return response()->json([
			'success' => $success,
			'message' => $success ? 'Payment verified' : 'Payment not successful',
			'data' => [
				'transaction' => $transaction->fresh(['package', 'item']),
				'paystack' => $res,
			],
		], 200);
	}

	/**
	 * Paystack webhook endpoint.
	 * Configure this URL in Paystack dashboard.
	 */
	public function webhook(Request $request)
	{
		$payload = $request->getContent();
		$signature = (string) $request->header('x-paystack-signature');

		$expected = $this->paystack->computeWebhookSignature($payload, $this->paystack->getWebhookSecret());
		if (!hash_equals($expected, $signature)) {
			return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
		}

		$event = $request->input('event');
		$data = $request->input('data', []);
		$reference = (string) ($data['reference'] ?? '');

		if (!$reference) {
			return response()->json(['success' => false, 'message' => 'Missing reference'], 400);
		}

		$transaction = Transaction::with('package')->where('reference', $reference)->first();
		if (!$transaction) {
			// Acknowledge anyway so Paystack doesn't retry forever.
			return response()->json(['success' => true], 200);
		}

		// Store payload for audit/debug.
		$transaction->update([
			'gateway_response' => $request->all(),
		]);

		if ($event === 'charge.success') {
			$transaction->update([
				'status' => 'success',
				'currency' => (string) ($data['currency'] ?? $transaction->currency),
				'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : $transaction->gateway_transaction_id,
				'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : $transaction->paid_at,
			]);

			$this->fulfillIfNeeded($transaction);
		}

		return response()->json(['success' => true], 200);
	}

	private function fulfillIfNeeded(Transaction $transaction): void
	{
		// Idempotency: only fulfill once.
		if ($transaction->fulfilled_at !== null) {
			return;
		}

		DB::transaction(function () use ($transaction) {
			$locked = Transaction::with(['package', 'user', 'item'])
				->whereKey($transaction->getKey())
				->lockForUpdate()
				->firstOrFail();

			if ($locked->fulfilled_at !== null) {
				return;
			}

			$package = $locked->package;
			$user = $locked->user;

			if (!$package || !$user) {
				return;
			}

			if ($package->package_type === 'upload') {
				$amount = (int) ($package->number_of_listings ?? 0);
				$user->addUploadsForCategory($package->category_id, $amount);
			}

			if ($package->package_type === 'promotion') {
				if (!$locked->item_id) {
					throw ValidationException::withMessages([
						'item_id' => ['Transaction item_id is missing for promotion package.'],
					]);
				}

				$days = (int) ($package->promotion_days ?? 0);
				if ($days <= 0) {
					throw ValidationException::withMessages([
						'package_id' => ['Promotion package has invalid promotion_days.'],
					]);
				}

				Promotion::create([
					'user_id' => $user->id,
					'item_id' => $locked->item_id,
					'start_at' => now(),
					'end_at' => now()->addDays($days),
					'status' => 'active',
				]);
			}

			$locked->update([
				'fulfilled_at' => now(),
			]);
		});
	}
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\Transaction;
use App\Services\PaystackService;
use App\Services\PaystackSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaystackController extends Controller
{
	public function __construct(
		private readonly PaystackService $paystack,
	) {}

	/**
	 * Start a Paystack payment for a package.
	 * Flutter should open the returned authorization_url.
	 */
	public function initialize(Request $request)
	{
		if (!PaystackSettingsService::isPaystackConfigured()) {
			return response()->json([
				'success' => false,
				'message' => 'Paystack is not configured',
			], 503);
		}

		$data = $request->validate([
			'package_id' => ['required', 'string', 'exists:packages,id'],
			'item_id' => ['nullable', 'string', 'exists:items,id'],
			'email' => ['nullable', 'email'],
			'callback_url' => ['nullable', 'url'],
		]);

		$user = $request->user();
		$package = Package::with('country')->findOrFail($data['package_id']);

		// Packages are country-specific in your UI/API.
		if ($package->country_id !== null && $user->country_id !== null && $package->country_id !== $user->country_id) {
			throw ValidationException::withMessages([
				'package_id' => ['Package is not available for your country.'],
			]);
		}

		$item = null;
		if ($package->package_type === 'promotion') {
			if (empty($data['item_id'])) {
				throw ValidationException::withMessages([
					'item_id' => ['item_id is required for promotion packages.'],
				]);
			}

			$item = Item::findOrFail($data['item_id']);
			if ((string) $item->user_id !== (string) $user->id) {
				throw ValidationException::withMessages([
					'item_id' => ['You can only promote your own item.'],
				]);
			}
		}

		$reference = $this->paystack->generateReference('gag');
		$amount = (float) $package->price;
		$amountInKobo = (int) round($amount * 100);

		$transaction = Transaction::create([
			'user_id' => $user->id,
			'package_id' => $package->id,
			'item_id' => $item?->id,
			'amount' => $amount,
			'payment_channel' => 'paystack',
			'status' => 'initialized',
			'reference' => $reference,
			'gateway' => 'paystack',
			'metadata' => [
				'package_type' => $package->package_type,
			],
		]);

		$payload = [
			'email' => $data['email'] ?? $user->email,
			'amount' => $amountInKobo,
			'reference' => $reference,
			'metadata' => [
				'transaction_id' => (string) $transaction->id,
				'user_id' => (string) $user->id,
				'package_id' => (string) $package->id,
				'item_id' => $item ? (string) $item->id : null,
				'package_type' => $package->package_type,
			],
		];

		if (!empty($data['callback_url'])) {
			$payload['callback_url'] = $data['callback_url'];
		} else {
			$defaultCallback = (string) PaystackSettingsService::getSetting('paystack_callback_url', '');
			if ($defaultCallback !== '') {
				$payload['callback_url'] = $defaultCallback;
			}
		}

		$res = $this->paystack->initializeTransaction($payload);

		$transaction->update([
			'gateway_response' => $res,
		]);

		return response()->json([
			'success' => true,
			'message' => 'Payment initialized',
			'data' => [
				'reference' => $reference,
				'authorization_url' => data_get($res, 'data.authorization_url'),
				'access_code' => data_get($res, 'data.access_code'),
				'transaction' => $transaction,
			],
		], 200);
	}

	/**
	 * Public-safe Paystack config for frontend clients.
	 */
	public function config(Request $request)
	{
		return response()->json([
			'success' => true,
			'message' => 'Paystack config fetched',
			'data' => PaystackSettingsService::getPublicConfig(),
		], 200);
	}

	/**
	 * Verify a Paystack transaction by reference.
	 * Flutter should call this after the user completes payment.
	 */
	public function verify(Request $request)
	{
		if (!PaystackSettingsService::isPaystackConfigured()) {
			return response()->json([
				'success' => false,
				'message' => 'Paystack is not configured',
			], 503);
		}

		$data = $request->validate([
			'reference' => ['required', 'string'],
		]);

		$user = $request->user();
		$transaction = Transaction::with(['package'])->where('reference', $data['reference'])->firstOrFail();

		if ((string) $transaction->user_id !== (string) $user->id) {
			return response()->json([
				'success' => false,
				'message' => 'Forbidden',
			], 403);
		}

		$res = $this->paystack->verifyTransaction($data['reference']);
		$paystackStatus = (string) data_get($res, 'data.status');
		$paidAmount = (int) data_get($res, 'data.amount', 0); // kobo
		$currency = (string) data_get($res, 'data.currency');
		$paidAt = data_get($res, 'data.paid_at');
		$gatewayTxnId = data_get($res, 'data.id');

		$expectedAmount = (int) round(((float) $transaction->amount) * 100);
		if ($expectedAmount > 0 && $paidAmount > 0 && $expectedAmount !== $paidAmount) {
			$transaction->update([
				'status' => 'amount_mismatch',
				'gateway_response' => $res,
				'currency' => $currency ?: $transaction->currency,
				'gateway_transaction_id' => $gatewayTxnId ? (string) $gatewayTxnId : $transaction->gateway_transaction_id,
			]);

			return response()->json([
				'success' => false,
				'message' => 'Payment amount mismatch',
			], 409);
		}

		$success = $paystackStatus === 'success';
		$transaction->update([
			'status' => $success ? 'success' : ($paystackStatus ?: 'failed'),
			'gateway_response' => $res,
			'currency' => $currency ?: $transaction->currency,
			'gateway_transaction_id' => $gatewayTxnId ? (string) $gatewayTxnId : $transaction->gateway_transaction_id,
			'paid_at' => $success && $paidAt ? Carbon::parse($paidAt) : $transaction->paid_at,
		]);

		if ($success) {
			$this->fulfillIfNeeded($transaction);
		}

		return response()->json([
			'success' => $success,
			'message' => $success ? 'Payment verified' : 'Payment not successful',
			'data' => [
				'transaction' => $transaction->fresh(['package', 'item']),
				'paystack' => $res,
			],
		], 200);
	}

	/**
	 * Paystack webhook endpoint.
	 * Configure this URL in Paystack dashboard.
	 */
	public function webhook(Request $request)
	{
		$payload = $request->getContent();
		$signature = (string) $request->header('x-paystack-signature');

		$expected = $this->paystack->computeWebhookSignature($payload, $this->paystack->getWebhookSecret());
		if (!hash_equals($expected, $signature)) {
			return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
		}

		$event = $request->input('event');
		$data = $request->input('data', []);
		$reference = (string) ($data['reference'] ?? '');

		if (!$reference) {
			return response()->json(['success' => false, 'message' => 'Missing reference'], 400);
		}

		$transaction = Transaction::with('package')->where('reference', $reference)->first();
		if (!$transaction) {
			// Acknowledge anyway so Paystack doesn't retry forever.
			return response()->json(['success' => true], 200);
		}

		// Store payload for audit/debug.
		$transaction->update([
			'gateway_response' => $request->all(),
		]);

		if ($event === 'charge.success') {
			$transaction->update([
				'status' => 'success',
				'currency' => (string) ($data['currency'] ?? $transaction->currency),
				'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : $transaction->gateway_transaction_id,
				'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : $transaction->paid_at,
			]);

			$this->fulfillIfNeeded($transaction);
		}

		return response()->json(['success' => true], 200);
	}

	private function fulfillIfNeeded(Transaction $transaction): void
	{
		// Idempotency: only fulfill once.
		if ($transaction->fulfilled_at !== null) {
			return;
		}

		DB::transaction(function () use ($transaction) {
			$locked = Transaction::with(['package', 'user', 'item'])
				->whereKey($transaction->getKey())
				->lockForUpdate()
				->firstOrFail();

			if ($locked->fulfilled_at !== null) {
				return;
			}

			$package = $locked->package;
			$user = $locked->user;

			if (!$package || !$user) {
				return;
			}

			if ($package->package_type === 'upload') {
				$amount = (int) ($package->number_of_listings ?? 0);
				$user->addUploadsForCategory($package->category_id, $amount);
			}

			if ($package->package_type === 'promotion') {
				if (!$locked->item_id) {
					throw ValidationException::withMessages([
						'item_id' => ['Transaction item_id is missing for promotion package.'],
					]);
				}

				$days = (int) ($package->promotion_days ?? 0);
				if ($days <= 0) {
					throw ValidationException::withMessages([
						'package_id' => ['Promotion package has invalid promotion_days.'],
					]);
				}

				Promotion::create([
					'user_id' => $user->id,
					'item_id' => $locked->item_id,
					'start_at' => now(),
					'end_at' => now()->addDays($days),
					'status' => 'active',
				]);
			}

			$locked->update([
				'fulfilled_at' => now(),
			]);
		});
	}
}

