<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\WalletBalance;
use App\Models\WalletLedger;
use App\Models\WalletTopup;
use App\Services\PaystackService;
use App\Services\PaystackSettingsService;
use App\Services\PackageFulfillmentService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaystackController extends Controller
{
	public function __construct(
		private readonly PaystackService $paystack,
		private readonly PackageFulfillmentService $packageFulfillment,
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

		try {
			$user = $request->user();
			$package = Package::with('country')->findOrFail($data['package_id']);

			// Packages are country-specific in your UI/API (compare as ints — DB/API may mix string/int).
			if ($package->country_id !== null && $user->country_id !== null) {
				if ((int) $package->country_id !== (int) $user->country_id) {
					throw ValidationException::withMessages([
						'package_id' => ['Package is not available for your country.'],
					]);
				}
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
			// For upload packages, allow linking the payment to a draft item for recovery/resume.
			if ($item === null && !empty($data['item_id'])) {
				$linked = Item::findOrFail($data['item_id']);
				if ((string) $linked->user_id !== (string) $user->id) {
					throw ValidationException::withMessages([
						'item_id' => ['Forbidden'],
					]);
				}
				$item = $linked;
			}

			$reference = $this->paystack->generateReference('gag');
			$amount = (float) $package->price;
			$amountInKobo = (int) round($amount * 100);

			$rawEmail = $data['email'] ?? $user->email;
			$email = trim((string) ($rawEmail ?? ''));
			if ($email === '') {
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
					'gateway_response' => $this->encodeGatewayResponseForStorage(
						is_array($body) ? $body : ['error' => $e->getMessage()],
					),
				]);

				return response()->json([
					'success' => false,
					'message' => $message,
				], 422);
			} catch (ConnectionException $e) {
				$transaction->update([
					'status' => 'gateway_error',
					'gateway_response' => $this->encodeGatewayResponseForStorage([
						'error' => $e->getMessage(),
					]),
				]);

				return response()->json([
					'success' => false,
					'message' => 'Unable to reach payment gateway. Try again shortly.',
				], 503);
			}

			$res = is_array($res) ? $res : [];

			$transaction->update([
				'gateway_response' => $this->encodeGatewayResponseForStorage($res),
			]);

			$authorizationUrl = data_get($res, 'data.authorization_url');
			if (!is_string($authorizationUrl) || trim($authorizationUrl) === '') {
				$transaction->update(['status' => 'gateway_error']);

				return response()->json([
					'success' => false,
					'message' => 'Payment gateway did not return a checkout URL.',
				], 422);
			}

			// Omit gateway_response from JSON: large nested Paystack payloads can break encoding or responses.
			$transaction->refresh();
			$transactionPayload = Arr::except($transaction->toArray(), ['gateway_response']);

			return response()->json([
				'success' => true,
				'message' => 'Payment initialized',
				'data' => [
					'reference' => $reference,
					'authorization_url' => $authorizationUrl,
					'access_code' => data_get($res, 'data.access_code'),
					'transaction' => $transactionPayload,
				],
			], 200);
		} catch (ValidationException $e) {
			throw $e;
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			throw $e;
		} catch (QueryException $e) {
			report($e);

			return response()->json([
				'success' => false,
				'message' => config('app.debug')
					? $e->getMessage()
					: 'Could not save payment (database error). Run migrations and ensure utf8mb4. See laravel.log.',
			], 500);
		} catch (\Throwable $e) {
			report($e);
			$kind = class_basename($e);
			$prev = $e->getPrevious();
			if ($prev instanceof \Throwable) {
				$kind .= ' ← ' . class_basename($prev);
			}

			return response()->json([
				'success' => false,
				'message' => config('app.debug')
					? $e->getMessage()
					: "Unable to start payment ($kind). Check laravel.log or enable APP_DEBUG temporarily.",
			], 500);
		}
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

		try {
			$res = $this->paystack->verifyTransaction($data['reference']);
		} catch (RequestException $e) {
			$body = $e->response?->json();
			$message = is_array($body) && isset($body['message']) && is_string($body['message'])
				? $body['message']
				: 'Payment verification failed.';
			$transaction->update([
				'status' => 'verification_error',
				'gateway_response' => $this->encodeGatewayResponseForStorage(
					is_array($body) ? $body : ['error' => $e->getMessage()],
				),
			]);

			return response()->json([
				'success' => false,
				'message' => $message,
			], 422);
		} catch (ConnectionException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Unable to reach payment gateway. Try again shortly.',
			], 503);
		}

		$res = is_array($res) ? $res : [];

		$paystackStatus = (string) data_get($res, 'data.status');
		$paidAmount = (int) data_get($res, 'data.amount', 0); // kobo
		$currency = (string) data_get($res, 'data.currency');
		$paidAt = data_get($res, 'data.paid_at');
		$gatewayTxnId = data_get($res, 'data.id');

		$expectedAmount = (int) round(((float) $transaction->amount) * 100);

		if ($expectedAmount > 0 && $paidAmount > 0 && $expectedAmount !== $paidAmount) {
			$transaction->update([
				'status' => 'amount_mismatch',
				'gateway_response' => $this->encodeGatewayResponseForStorage($res),
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
			'gateway_response' => $this->encodeGatewayResponseForStorage($res),
			'currency' => $currency ?: $transaction->currency,
			'gateway_transaction_id' => $gatewayTxnId ? (string) $gatewayTxnId : $transaction->gateway_transaction_id,
			'paid_at' => $success && $paidAt ? Carbon::parse($paidAt) : $transaction->paid_at,
		]);

		if ($success) {
			$this->packageFulfillment->fulfillIfNeeded($transaction);
		}

		$tx = $transaction->fresh(['package', 'item']);
		$transactionPayload = $tx
			? Arr::except($tx->toArray(), ['gateway_response'])
			: [];

		return response()->json([
			'success' => $success,
			'message' => $success ? 'Payment verified' : 'Payment not successful',
			'data' => [
				'transaction' => $transactionPayload,
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
		$walletTopup = null;

		if (!$transaction) {
			$walletTopup = WalletTopup::where('reference', $reference)->first();
			if (!$walletTopup) {
				// Acknowledge anyway so Paystack doesn't retry forever.
				return response()->json(['success' => true], 200);
			}
		}

		$gatewayResponse = $this->encodeGatewayResponseForStorage($request->all());

		// Store payload for audit/debug.
		if ($transaction) {
			$transaction->update([
				'gateway_response' => $gatewayResponse,
			]);
		} elseif ($walletTopup) {
			$walletTopup->update([
				'metadata' => array_merge(
					(array) ($walletTopup->metadata ?? []),
					['gateway_response' => $gatewayResponse],
				),
			]);
		}

		if ($event === 'charge.success') {
			if ($transaction) {
				$transaction->update([
					'status' => 'success',
					'currency' => (string) ($data['currency'] ?? $transaction->currency),
					'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : $transaction->gateway_transaction_id,
					'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : $transaction->paid_at,
				]);

				$this->packageFulfillment->fulfillIfNeeded($transaction);
			} elseif ($walletTopup) {
				DB::transaction(function () use ($walletTopup, $data, $reference) {
					$lockedTopup = WalletTopup::where('reference', $reference)->lockForUpdate()->first();
					if (!$lockedTopup) {
						return;
					}

					// Idempotency: only credit once.
					if ($lockedTopup->status === 'success') {
						return;
					}

					$paidAt = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
					$amount = (float) $lockedTopup->amount;
					$gatewayTxnId = isset($data['id']) ? (string) $data['id'] : null;

					$lockedTopup->update([
						'status' => 'success',
						'paid_at' => $paidAt,
						'metadata' => array_merge(
							(array) ($lockedTopup->metadata ?? []),
							[
								'gateway_transaction_id' => $gatewayTxnId,
								'paid_at' => $paidAt->toIso8601String(),
							]
						),
					]);

					$ledger = WalletLedger::where('reference', $reference)->lockForUpdate()->first();

					$balance = WalletBalance::where('user_id', $lockedTopup->user_id)->lockForUpdate()->first();
					if (!$balance) {
						$balance = WalletBalance::create([
							'user_id' => $lockedTopup->user_id,
							'balance' => 0,
						]);
					}

					// Apply credit if ledger wasn't already completed.
					if ($ledger && $ledger->status !== 'completed') {
						$balance->increment('balance', $amount);
						$ledger->update([
							'status' => 'completed',
							'metadata' => array_merge(
								(array) ($ledger->metadata ?? []),
								[
									'paid_at' => $paidAt->toIso8601String(),
									'gateway_transaction_id' => $gatewayTxnId,
								],
							),
						]);
					}
				});
			}
		}

		return response()->json(['success' => true], 200);
	}

	/**
	 * Paystack JSON may contain UTF-8 sequences MySQL utf8 (3-byte) rejects; normalize before json column write.
	 *
	 * @param  array<string, mixed>  $payload
	 * @return array<string, mixed>
	 */
	private function encodeGatewayResponseForStorage(array $payload): array
	{
		if ($payload === []) {
			return [];
		}

		$json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
		if ($json === false || $json === '') {
			return ['_note' => 'gateway_payload_could_not_be_encoded'];
		}

		$decoded = json_decode($json, true);

		return is_array($decoded) ? $decoded : ['_note' => 'gateway_payload_redecode_failed'];
	}

	private function fulfillIfNeeded(Transaction $transaction): void
	{
		$this->packageFulfillment->fulfillIfNeeded($transaction);
	}
}
