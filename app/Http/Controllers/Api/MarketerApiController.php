<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Services\MarketerCommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MarketerApiController extends Controller
{
    public function __construct(
        private readonly MarketerCommissionService $commissionService,
    ) {}

    public function promoCodes(Request $request)
    {
        $user = $this->requireMarketer($request);

        $codes = PromoCode::query()
            ->where('marketer_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PromoCode $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'discount_type' => $c->discount_type,
                'discount_value' => (float) $c->discount_value,
                'expires_at' => $c->expires_at?->toIso8601String(),
                'max_uses' => $c->max_uses,
                'uses_count' => $c->uses_count,
                'active' => $c->active,
                'package_id' => $c->package_id,
            ]);

        return response()->json([
            'success' => true,
            'data' => ['promo_codes' => $codes],
        ], 200);
    }

    public function stats(Request $request)
    {
        $user = $this->requireMarketer($request);

        $range = (string) $request->query('range', 'month');
        [$from, $to] = $this->parseRange($range);

        $summary = $this->commissionService->aggregateForMarketer($user, $from, $to);
        $perCode = $this->commissionService->statsPerCode($user, $from, $to);

        return response()->json([
            'success' => true,
            'data' => [
                'range' => $range,
                'from' => $from?->toIso8601String(),
                'to' => $to?->toIso8601String(),
                'summary' => $summary,
                'per_code' => $perCode,
            ],
        ], 200);
    }

    private function requireMarketer(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('marketer')) {
            throw new AccessDeniedHttpException('Marketer access required.');
        }

        return $user;
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
