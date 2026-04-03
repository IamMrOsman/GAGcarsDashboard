<?php

namespace App\Services;

use App\Models\Setting;

class MarketerSettingsService
{
    public static function getDefaultCommissionRate(): float
    {
        $setting = Setting::where('key_slug', 'marketer')->first();
        if (! $setting || ! is_array($setting->data)) {
            return 0.0;
        }

        $raw = $setting->data['default_commission_rate'] ?? 0;

        return (float) $raw;
    }
}
