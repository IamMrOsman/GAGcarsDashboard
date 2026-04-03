<?php

namespace App\Services;

use App\Models\MarketerProfile;
use App\Models\PromoCode;
use App\Models\PromoRedemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketerCommissionService
{
    /**
     * @return array{
     *   net_revenue: float,
     *   total_discount: float,
     *   redemption_count: int,
     *   listings_count: int,
     *   sold_listings_count: int,
     *   commission_rate: float,
     *   estimated_commission: float
     * }
     */
    public function aggregateForMarketer(User $marketer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = PromoRedemption::query()
            ->whereHas('promoCode', fn ($q) => $q->where('marketer_id', $marketer->id));

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $netRevenue = (clone $query)->sum('paid_amount');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $redemptionCount = (clone $query)->count();

        $promoIds = PromoCode::query()->where('marketer_id', $marketer->id)->pluck('id');

        $listingsQuery = DB::table('items')
            ->whereIn('promo_code_id', $promoIds)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));

        $listingsCount = (clone $listingsQuery)->count();
        $soldListingsCount = (clone $listingsQuery)->where('status', 'sold')->count();

        $rate = $this->resolveCommissionRate($marketer);
        $estimatedCommission = round((float) $netRevenue * $rate, 2);

        return [
            'net_revenue' => (float) $netRevenue,
            'total_discount' => (float) $totalDiscount,
            'redemption_count' => (int) $redemptionCount,
            'listings_count' => (int) $listingsCount,
            'sold_listings_count' => (int) $soldListingsCount,
            'commission_rate' => $rate,
            'estimated_commission' => $estimatedCommission,
        ];
    }

    public function resolveCommissionRate(User $marketer): float
    {
        $profile = MarketerProfile::query()->where('user_id', $marketer->id)->where('active', true)->first();
        if ($profile) {
            return (float) $profile->commission_rate;
        }

        return MarketerSettingsService::getDefaultCommissionRate();
    }

    /**
     * Per-code stats for a marketer's promo codes.
     *
     * @return list<array<string, mixed>>
     */
    public function statsPerCode(User $marketer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $codes = PromoCode::query()->where('marketer_id', $marketer->id)->get();
        $out = [];

        foreach ($codes as $code) {
            $q = PromoRedemption::query()->where('promo_code_id', $code->id);
            if ($from) {
                $q->where('created_at', '>=', $from);
            }
            if ($to) {
                $q->where('created_at', '<=', $to);
            }

            $net = (clone $q)->sum('paid_amount');
            $discount = (clone $q)->sum('discount_amount');
            $uses = (clone $q)->count();

            $listings = DB::table('items')
                ->where('promo_code_id', $code->id)
                ->when($from, fn ($qq) => $qq->where('created_at', '>=', $from))
                ->when($to, fn ($qq) => $qq->where('created_at', '<=', $to))
                ->count();

            $sold = DB::table('items')
                ->where('promo_code_id', $code->id)
                ->where('status', 'sold')
                ->when($from, fn ($qq) => $qq->where('created_at', '>=', $from))
                ->when($to, fn ($qq) => $qq->where('created_at', '<=', $to))
                ->count();

            $rate = $this->resolveCommissionRate($marketer);

            $out[] = [
                'promo_code_id' => $code->id,
                'code' => $code->code,
                'active' => $code->active,
                'uses_count' => $uses,
                'listings_count' => $listings,
                'sold_listings_count' => $sold,
                'net_revenue' => (float) $net,
                'total_discount' => (float) $discount,
                'estimated_commission' => round((float) $net * $rate, 2),
            ];
        }

        return $out;
    }
}
