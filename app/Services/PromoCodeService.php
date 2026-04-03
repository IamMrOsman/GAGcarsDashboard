<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PromoCodeService
{
    public static function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    /**
     * @return array{promo_code: PromoCode, gross: float, discount: float, paid: float, currency: string}
     */
    public static function validateAndQuote(
        string $rawCode,
        Package $package,
        ?User $user = null,
    ): array {
        $normalized = self::normalizeCode($rawCode);
        if ($normalized === '') {
            throw ValidationException::withMessages([
                'promo_code' => ['Enter a promo code.'],
            ]);
        }

        if (($package->package_type ?? '') !== 'upload') {
            throw ValidationException::withMessages([
                'promo_code' => ['Promo codes apply to upload packages only.'],
            ]);
        }

        $promo = PromoCode::query()
            ->whereRaw('UPPER(code) = ?', [$normalized])
            ->first();

        if (! $promo || ! $promo->active) {
            throw ValidationException::withMessages([
                'promo_code' => ['Invalid or inactive promo code.'],
            ]);
        }

        if ($promo->isExpired()) {
            throw ValidationException::withMessages([
                'promo_code' => ['This promo code has expired.'],
            ]);
        }

        if (! $promo->hasUsesRemaining()) {
            throw ValidationException::withMessages([
                'promo_code' => ['This promo code has reached its usage limit.'],
            ]);
        }

        if ($promo->package_id !== null && (string) $promo->package_id !== (string) $package->id) {
            throw ValidationException::withMessages([
                'promo_code' => ['This promo code does not apply to the selected package.'],
            ]);
        }

        $gross = (float) $package->price;
        $discount = self::computeDiscount($promo, $gross);
        $paid = max(0, round($gross - $discount, 2));

        if ($paid <= 0) {
            throw ValidationException::withMessages([
                'promo_code' => ['Discount would reduce the price below the minimum allowed.'],
            ]);
        }

        return [
            'promo_code' => $promo,
            'gross' => $gross,
            'discount' => $discount,
            'paid' => $paid,
            'currency' => 'GHS',
        ];
    }

    public static function computeDiscount(PromoCode $promo, float $grossAmount): float
    {
        if ($promo->discount_type === PromoCode::DISCOUNT_PERCENT) {
            $pct = (float) $promo->discount_value;
            $pct = min(100, max(0, $pct));

            return round($grossAmount * ($pct / 100), 2);
        }

        $fixed = (float) $promo->discount_value;

        return round(min($fixed, $grossAmount), 2);
    }

    /**
     * Validate a code for attaching to a draft (no package scope yet).
     */
    public static function validateForDraft(?string $rawCode): ?PromoCode
    {
        if ($rawCode === null || trim($rawCode) === '') {
            return null;
        }

        $normalized = self::normalizeCode($rawCode);
        $promo = PromoCode::query()
            ->whereRaw('UPPER(code) = ?', [$normalized])
            ->first();

        if (! $promo || ! $promo->active) {
            throw ValidationException::withMessages([
                'promo_code' => ['Invalid or inactive promo code.'],
            ]);
        }

        if ($promo->isExpired()) {
            throw ValidationException::withMessages([
                'promo_code' => ['This promo code has expired.'],
            ]);
        }

        if (! $promo->hasUsesRemaining()) {
            throw ValidationException::withMessages([
                'promo_code' => ['This promo code has reached its usage limit.'],
            ]);
        }

        return $promo;
    }

    public static function tryQuote(string $rawCode, Package $package): array
    {
        try {
            $q = self::validateAndQuote($rawCode, $package);

            return [
                'valid' => true,
                'gross' => $q['gross'],
                'discount' => $q['discount'],
                'paid' => $q['paid'],
                'promo_code_id' => $q['promo_code']->id,
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first();

            return [
                'valid' => false,
                'message' => is_string($msg) ? $msg : 'Invalid promo code.',
            ];
        }
    }

    /**
     * Draft-only check (no package quote). Used when package is not yet chosen.
     *
     * @return array{valid: bool, message?: string, promo_code_id?: string}
     */
    public static function tryDraftValidate(string $rawCode): array
    {
        try {
            $promo = self::validateForDraft($rawCode);

            return [
                'valid' => true,
                'promo_code_id' => $promo->id,
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first();

            return [
                'valid' => false,
                'message' => is_string($msg) ? $msg : 'Invalid promo code.',
            ];
        }
    }
}
