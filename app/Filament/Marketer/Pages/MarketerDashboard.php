<?php

namespace App\Filament\Marketer\Pages;

use App\Models\PromoCode;
use App\Services\MarketerCommissionService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class MarketerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.marketer.pages.marketer-dashboard';

    protected static ?string $title = 'My performance';

    #[Url]
    public string $range = 'month';

    protected function getCommissionService(): MarketerCommissionService
    {
        return app(MarketerCommissionService::class);
    }

    public function getSummaryProperty(): array
    {
        $user = auth()->user();
        [$from, $to] = $this->parseRange($this->range);

        return $this->getCommissionService()->aggregateForMarketer($user, $from, $to);
    }

    public function getPerCodeProperty(): array
    {
        $user = auth()->user();
        [$from, $to] = $this->parseRange($this->range);

        return $this->getCommissionService()->statsPerCode($user, $from, $to);
    }

    public function getCodesProperty()
    {
        return PromoCode::query()
            ->where('marketer_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function parseRange(string $range): array
    {
        $now = Carbon::now();

        return match ($range) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'all' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
