<?php

namespace App\Services;

use App\Models\Setting;

class PaymentRequirementService
{
    /**
     * Check if payment is required for a given country and category
     * 
     * @param string $countrySlug
     * @param string|null $categorySlug
     * @return array ['require_payment' => bool, 'reason' => string]
     */
    public function checkPaymentRequirement(string $countrySlug, ?string $categorySlug = null): array
    {
        // Check payment requirement hierarchy:
        // 1. Category-specific (highest priority)
        // 2. Country-specific 
        // 3. Global (lowest priority)

        $requirePayment = false;
        $reason = '';

        // 1. Check category-specific payment requirement
        if ($categorySlug) {
            $categoryPaymentSetting = Setting::where('key_slug', 'require_payment_for_category_' . $categorySlug)->first();
            if ($categoryPaymentSetting) {
                $requirePayment = $categoryPaymentSetting->value === 'true';
                $reason = $requirePayment ? "Payment required for {$categorySlug} category" : "Payment not required for {$categorySlug} category";
            }
        }

        // 2. If no category-specific setting, check country-specific
        if (!$requirePayment && !$reason) {
            $countryPaymentSetting = Setting::where('key_slug', 'require_payment_for_' . $countrySlug)->first();
            if ($countryPaymentSetting) {
                $requirePayment = $countryPaymentSetting->value === 'true';
                $reason = $requirePayment ? "Payment required for {$countrySlug}" : "Payment not required for {$countrySlug}";
            }
        }

        // 3. If no country-specific setting, check global
        if (!$requirePayment && !$reason) {
            $globalPaymentSetting = Setting::where('key_slug', 'require_payment_for_all')->first();
            if ($globalPaymentSetting) {
                $requirePayment = $globalPaymentSetting->value === 'true';
                $reason = $requirePayment ? "Global payment required" : "Global payment not required";
            }
        }

        return [
            'require_payment' => $requirePayment,
            'reason' => $reason ?: 'No payment requirement found'
        ];
    }

    /**
     * Check if payment is required for a user's country and optional category
     * 
     * @param \App\Models\User $user
     * @param string|null $categorySlug
     * @return array ['require_payment' => bool, 'reason' => string]
     */
    public function checkPaymentRequirementForUser($user, ?string $categorySlug = null): array
    {
        return $this->checkPaymentRequirement($user->country->slug, $categorySlug);
    }

    /**
     * Check if payment is required for an item
     * 
     * @param \App\Models\Item $item
     * @return array ['require_payment' => bool, 'reason' => string]
     */
    public function checkPaymentRequirementForItem($item): array
    {
        return $this->checkPaymentRequirement($item->user->country->slug, $item->category->slug);
    }
}
