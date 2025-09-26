<?php

namespace App\Services;

use App\Models\Setting;

class ApprovalRequirementService
{
    /**
     * Check if listing approval is required for a given country and category
     * 
     * @param string $countrySlug
     * @param string|null $categorySlug
     * @return array ['require_approval' => bool, 'reason' => string]
     */
    public function checkApprovalRequirement(string $countrySlug, ?string $categorySlug = null): array
    {
        // Check approval requirement hierarchy:
        // 1. Category-specific (highest priority)
        // 2. Country-specific 
        // 3. Global (lowest priority)

        $requireApproval = false;
        $reason = '';

        // 1. Check category-specific approval requirement
        if ($categorySlug) {
            $categoryApprovalSetting = Setting::where('key_slug', 'require_listing_approval_for_category_' . $categorySlug)->first();
            if ($categoryApprovalSetting) {
                $requireApproval = $categoryApprovalSetting->value === 'true';
                $reason = $requireApproval ? "Listing approval required for {$categorySlug} category" : "Listing approval not required for {$categorySlug} category";
            }
        }

        // 2. If no category-specific setting, check country-specific
        if (!$requireApproval && !$reason) {
            $countryApprovalSetting = Setting::where('key_slug', 'require_listing_approval_for_' . $countrySlug)->first();
            if ($countryApprovalSetting) {
                $requireApproval = $countryApprovalSetting->value === 'true';
                $reason = $requireApproval ? "Listing approval required for {$countrySlug}" : "Listing approval not required for {$countrySlug}";
            }
        }

        // 3. If no country-specific setting, check global
        if (!$requireApproval && !$reason) {
            $globalApprovalSetting = Setting::where('key_slug', 'require_listing_approval_for_all')->first();
            if ($globalApprovalSetting) {
                $requireApproval = $globalApprovalSetting->value === 'true';
                $reason = $requireApproval ? "Global listing approval required" : "Global listing approval not required";
            }
        }

        return [
            'require_approval' => $requireApproval,
            'reason' => $reason ?: 'No listing approval requirement found'
        ];
    }

    /**
     * Check if listing approval is required for a user's country and optional category
     * 
     * @param \App\Models\User $user
     * @param string|null $categorySlug
     * @return array ['require_approval' => bool, 'reason' => string]
     */
    public function checkApprovalRequirementForUser($user, ?string $categorySlug = null): array
    {
        return $this->checkApprovalRequirement($user->country->slug, $categorySlug);
    }

    /**
     * Check if listing approval is required for an item
     * 
     * @param \App\Models\Item $item
     * @return array ['require_approval' => bool, 'reason' => string]
     */
    public function checkApprovalRequirementForItem($item): array
    {
        return $this->checkApprovalRequirement($item->user->country->slug, $item->category->slug);
    }
}
