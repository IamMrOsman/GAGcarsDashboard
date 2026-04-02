<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\Transaction;
use App\Models\WalletBalance;
use App\Models\WalletLedger;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class WalletKpisOverview extends Widget
{
    protected static string $view = 'filament.widgets.wallet-kpis-overview';

    // Match the “feel” of other dashboard cards by not spanning the entire width on desktop.
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    /**
     * today | 7_days | 30_days | 90_days | all_time
     */
    public string $period = 'today';

    /**
     * Country ID or 'all' (string) for all countries.
     */
    public string $countryId = 'all';

    /**
     * @var array<string, float|int>
     */
    public array $kpis = [];

    public ?string $loadError = null;

    /**
     * @var array<int, array{id:string,name:string}>
     */
    public array $countries = [];

    public function mount(): void
    {
        $this->countries = Country::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Country $c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
            ->all();

        // Default to "all" so the dashboard never shows empty KPIs when no country is selected.
        $this->countryId = 'all';

        $this->loadKpis();
    }

    public function updatedPeriod(): void
    {
        $this->loadKpis();
    }

    public function updatedCountryId(): void
    {
        $this->loadKpis();
    }

    public function loadKpis(): void
    {
        $this->loadError = null;

        try {
            [$start, $end] = $this->resolvePeriodRange($this->period);

            $countryId = $this->countryId !== 'all' ? $this->countryId : null;

            $userCountryScope = function (Builder $q) use ($countryId): void {
                if ($countryId === null) {
                    return;
                }

                $q->where('country_id', $countryId);
            };

            // Total balance is current state; it doesn't make sense to time-filter it.
            $totalBalance = WalletBalance::query()
                ->when($countryId !== null, function (Builder $q) use ($userCountryScope) {
                    $q->whereHas('user', $userCountryScope);
                })
                ->sum('balance');

            $ledgerBase = WalletLedger::query()
                ->when($countryId !== null, function (Builder $q) use ($userCountryScope) {
                    $q->whereHas('user', $userCountryScope);
                })
                ->when($start !== null && $end !== null, function (Builder $q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end]);
                });

            $totalTopups = (clone $ledgerBase)
                ->where('direction', 'credit')
                ->where('status', 'completed')
                ->sum('amount');

            $totalSpending = (clone $ledgerBase)
                ->where('direction', 'debit')
                ->where('status', 'completed')
                ->sum('amount');

            // Spending splits by package type (stored in JSON metadata).
            $listingSpending = (clone $ledgerBase)
                ->where('direction', 'debit')
                ->where('status', 'completed')
                ->where('reason', 'package_purchase')
                ->where('metadata->package_type', 'upload')
                ->sum('amount');

            $promotionSpending = (clone $ledgerBase)
                ->where('direction', 'debit')
                ->where('status', 'completed')
                ->where('reason', 'package_purchase')
                ->where('metadata->package_type', 'promotion')
                ->sum('amount');

            $failedTransactions = (clone $ledgerBase)
                ->where('status', 'failed')
                ->count();

            $pendingTransactions = (clone $ledgerBase)
                ->where('status', 'pending')
                ->count();

            $revenue = Transaction::query()
                ->where('status', 'success')
                ->when($countryId !== null, function (Builder $q) use ($userCountryScope) {
                    $q->whereHas('user', $userCountryScope);
                })
                ->when($start !== null && $end !== null, function (Builder $q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end]);
                })
                ->sum('amount');

            $this->kpis = [
                'total_balance' => (float) $totalBalance,
                'total_topups' => (float) $totalTopups,
                'total_spending' => (float) $totalSpending,
                'listing_spending' => (float) $listingSpending,
                'promotion_spending' => (float) $promotionSpending,
                'revenue' => (float) $revenue,
                'failed_transactions' => (int) $failedTransactions,
                'pending_transactions' => (int) $pendingTransactions,
            ];
        } catch (\Throwable $e) {
            report($e);
            $this->loadError = 'Failed to load wallet KPIs. Please try again.';
            $this->kpis = [];
        }
    }

    /**
     * @return array{0:Carbon|null,1:Carbon|null}
     */
    private function resolvePeriodRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now],
            '7_days' => [$now->copy()->subDays(6)->startOfDay(), $now],
            '30_days' => [$now->copy()->subDays(29)->startOfDay(), $now],
            '90_days' => [$now->copy()->subDays(89)->startOfDay(), $now],
            'all_time' => [null, null],
            default => [$now->copy()->startOfDay(), $now],
        };
    }

    public function formatMoney(float $value): string
    {
        return 'GHC ' . number_format($value, 2);
    }
}

