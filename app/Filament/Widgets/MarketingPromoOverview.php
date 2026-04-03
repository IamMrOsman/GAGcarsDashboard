<?php

namespace App\Filament\Widgets;

use App\Models\PromoCode;
use App\Models\PromoRedemption;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class MarketingPromoOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $codes = PromoCode::query()->count();
        $redemptions = PromoRedemption::query()->count();
        $net = (float) PromoRedemption::query()->sum('paid_amount');
        $discount = (float) PromoRedemption::query()->sum('discount_amount');

        return [
            Stat::make('Marketing promo codes', (string) $codes)
                ->description('Active codes in system')
                ->icon('heroicon-o-ticket'),
            Stat::make('Total redemptions', (string) $redemptions)
                ->description('Completed uses')
                ->icon('heroicon-o-arrow-path'),
            Stat::make('Net revenue (promo checkouts)', 'GHS '.number_format($net, 2))
                ->description('After discount charged')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Discount given', 'GHS '.number_format($discount, 2))
                ->description('Total savings to users')
                ->icon('heroicon-o-gift'),
        ];
    }
}
