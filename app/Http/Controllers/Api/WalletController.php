<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\WalletBalance;
use App\Models\WalletLedger;
use App\Models\WalletTopup;
use App\Services\PackageFulfillmentService;
use App\Services\PaystackService;
use App\Services\PaystackSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly PackageFulfillmentService $packageFulfillment,
    ) {
    }

    public function balance(Request $request)
    {
        $user = $request->user();

        $balance = WalletBalance::where('user_id', $user->id)->value('balance')
            ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'Wallet balance fetched',
            'data' => [
                'balance' => (float) $balance,
            ],
        ], 200);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();

        $entries = WalletLedger::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'id',
                'direction',
                'amount',
                'reason',
                'status',
                'reference',
                'created_at',
                'metadata',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet transactions fetched',
            'data' => [
                'transactions' => $entries,
            ],
        ], 200);
    }

    public function initializeTopup(Request $request)
    {
        if (!PaystackSettingsService::isPaystackConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Paystack is not configured',
            ], 503);
        }

        $user = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $amount = (float) $data['amount'];
        $amountInKobo = (int) round($amount * 100);

        $reference = $this->paystack->generateReference('wallet_topup');

        $topup = null;
        $ledger = null;

        DB::transaction(function () use (
            $user,
            $reference,
            $amount,
            $amountInKobo,
            &$topup,
            &$ledger
        ) {
            $topup = WalletTopup::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'status' => 'initialized',
                'amount' => $amount,
                'metadata' => [
                    'amount_kobo' => $amountInKobo,
                ],
            ]);

            $ledger = WalletLedger::create([
                'user_id' => $user->id,
                'direction' => 'credit',
                'amount' => $amount,
                'reason' => 'wallet_topup',
                'reference' => $reference,
                'status' => 'pending',
                'metadata' => [
                    'wallet_topup_id' => (string) $topup->id,
                ],
            ]);
        });

        try {
            $payload = [
                'email' => $user->email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'metadata' => [
                    'wallet_topup_reference' => $reference,
                    'user_id' => (string) $user->id,
                    'wallet_topup_id' => (string) $topup->id,
                ],
            ];

            $defaultCallback = (string) PaystackSettingsService::getSetting('paystack_callback_url', '');
            if ($defaultCallback !== '') {
                $payload['callback_url'] = $defaultCallback;
            }

            $res = $this->paystack->initializeTransaction($payload);

            $authorizationUrl = data_get($res, 'data.authorization_url');
            if (!is_string($authorizationUrl) || trim($authorizationUrl) === '') {
                throw ValidationException::withMessages([
                    'authorization_url' => ['Payment gateway did not return an authorization url.'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet top-up initialized',
                'data' => [
                    'reference' => $reference,
                    'authorization_url' => $authorizationUrl,
                    'amount' => $amount,
                    'topup_id' => (string) $topup->id,
                ],
            ], 200);
        } catch (\Throwable $e) {
            // Mark pending wallet records as failed.
            WalletTopup::where('reference', $reference)->update([
                'status' => 'failed',
            ]);
            WalletLedger::where('reference', $reference)->update([
                'status' => 'failed',
            ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize wallet top-up',
            ], 422);
        }
    }

    public function purchasePackage(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'package_id' => ['required', 'string', 'exists:packages,id'],
            'item_id' => ['nullable', 'string', 'exists:items,id'],
        ]);

        $packageId = $data['package_id'];
        $itemId = $data['item_id'] ?? null;

        /** @var Package $package */
        $package = Package::findOrFail($packageId);

        $amount = (float) $package->price;
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Invalid package amount.'],
            ]);
        }

        if ($package->package_type === 'promotion' && empty($itemId)) {
            throw ValidationException::withMessages([
                'item_id' => ['item_id is required for promotion packages.'],
            ]);
        }

        if ($package->package_type === 'upload') {
            $itemId = null;
        }

        $reference = $this->paystack->generateReference('wallet_debit');

        $transaction = null;
        $ledger = null;

        DB::transaction(function () use (
            $user,
            $package,
            $amount,
            $itemId,
            $reference,
            &$transaction,
            &$ledger
        ) {
            $balanceRow = WalletBalance::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$balanceRow) {
                $balanceRow = WalletBalance::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                ]);
            }

            if ((float) $balanceRow->balance < (float) $amount) {
                throw ValidationException::withMessages([
                    'balance' => ['Insufficient wallet balance.'],
                ]);
            }

            $balanceRow->decrement('balance', $amount);

            $ledger = WalletLedger::create([
                'user_id' => $user->id,
                'direction' => 'debit',
                'amount' => $amount,
                'reason' => 'package_purchase',
                'reference' => $reference,
                'status' => 'pending',
                'metadata' => [
                    'package_id' => (string) $package->id,
                    'package_type' => (string) $package->package_type,
                    'item_id' => $itemId,
                ],
            ]);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'item_id' => $itemId,
                'amount' => $amount,
                'payment_channel' => 'wallet',
                'status' => 'success',
                'reference' => $reference,
                'gateway' => 'wallet',
                'currency' => $package->country?->currency_code,
                'metadata' => [
                    'package_type' => $package->package_type,
                    'package_id' => (string) $package->id,
                    'item_id' => $itemId,
                ],
            ]);
        });

        try {
            $this->packageFulfillment->fulfillIfNeeded($transaction);

            // Mark ledger entry completed after fulfillment success.
            $ledger->update([
                'status' => 'completed',
                'metadata' => array_merge(
                    (array) ($ledger->metadata ?? []),
                    [
                        'fulfilled_at' => Carbon::now()->toIso8601String(),
                    ],
                ),
            ]);
        } catch (\Throwable $e) {
            // Refund on fulfillment error.
            DB::transaction(function () use ($user, $amount, $ledger, $reference, $transaction) {
                WalletBalance::where('user_id', $user->id)->lockForUpdate()->increment('balance', $amount);

                if ($ledger) {
                    $ledger->update([
                        'status' => 'failed',
                    ]);
                }

                if ($transaction) {
                    $transaction->update([
                        'status' => 'failed',
                    ]);
                }
            });

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Wallet purchase failed to fulfill package.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet purchase successful',
            'data' => [
                'reference' => $reference,
                'package_id' => (string) $package->id,
                'item_id' => $itemId,
                'amount' => $amount,
            ],
        ], 200);
    }
}

