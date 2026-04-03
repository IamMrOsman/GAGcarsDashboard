<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PromoCode;
use App\Models\PromoRedemption;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PromoRedemptionService
{
    /**
     * Record redemption after successful payment + fulfillment (idempotent on transaction_id).
     */
    public function recordIfApplicable(Transaction $transaction): void
    {
        $meta = $transaction->metadata;
        if (! is_array($meta) || empty($meta['promo_code_id'])) {
            return;
        }

        $promoCodeId = (string) $meta['promo_code_id'];
        $promo = PromoCode::find($promoCodeId);
        if (! $promo) {
            return;
        }

        DB::transaction(function () use ($transaction, $promo, $meta) {
            $exists = PromoRedemption::query()
                ->where('transaction_id', $transaction->id)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return;
            }

            $gross = isset($meta['original_amount']) ? (float) $meta['original_amount'] : (float) $transaction->amount;
            $discount = isset($meta['promo_discount_amount']) ? (float) $meta['promo_discount_amount'] : 0.0;
            $paid = (float) $transaction->amount;

            $redemption = PromoRedemption::create([
                'promo_code_id' => $promo->id,
                'user_id' => $transaction->user_id,
                'item_id' => $transaction->item_id,
                'transaction_id' => $transaction->id,
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'paid_amount' => $paid,
                'currency' => (string) ($transaction->currency ?? 'GHS'),
            ]);

            $promo->increment('uses_count');

            if ($transaction->item_id) {
                Item::query()->whereKey($transaction->item_id)->update(['promo_code_id' => $promo->id]);
            }
        });
    }
}
