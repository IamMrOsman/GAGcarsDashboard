<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageFulfillmentService
{
    /**
     * Fulfill a paid package purchase (upload allowance or promotion activation).
     *
     * Idempotent: if the transaction was already fulfilled, it does nothing.
     */
    public function fulfillIfNeeded(Transaction $transaction): void
    {
        // Fast check to avoid unnecessary locking.
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
                        'item_id' => [
                            'Transaction item_id is missing for promotion package.',
                        ],
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
