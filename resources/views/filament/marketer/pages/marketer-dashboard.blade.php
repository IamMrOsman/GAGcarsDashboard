<x-filament-panels::page>
    <div class="space-y-6" wire:poll.30s>
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Period</label>
            <select
                wire:model.live="range"
                class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm"
            >
                <option value="day">Today</option>
                <option value="week">This week</option>
                <option value="month">This month</option>
                <option value="all">All time</option>
            </select>
        </div>

        @php($s = $this->getSummaryProperty())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">Net revenue</div>
                <div class="text-2xl font-bold">GHS {{ number_format($s['net_revenue'], 2) }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">Redemptions</div>
                <div class="text-2xl font-bold">{{ $s['redemption_count'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">Listings (attributed)</div>
                <div class="text-2xl font-bold">{{ $s['listings_count'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">Sold listings</div>
                <div class="text-2xl font-bold">{{ $s['sold_listings_count'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">Discount given</div>
                <div class="text-2xl font-bold">GHS {{ number_format($s['total_discount'], 2) }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800 ring-2 ring-primary-500/30">
                <div class="text-sm text-gray-500 dark:text-gray-400">Est. commission ({{ number_format($s['commission_rate'] * 100, 2) }}%)</div>
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">GHS {{ number_format($s['estimated_commission'], 2) }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow dark:bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 font-semibold">Per code</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Code</th>
                            <th class="px-4 py-2 text-right font-medium">Uses</th>
                            <th class="px-4 py-2 text-right font-medium">Listings</th>
                            <th class="px-4 py-2 text-right font-medium">Sold</th>
                            <th class="px-4 py-2 text-right font-medium">Net</th>
                            <th class="px-4 py-2 text-right font-medium">Est. commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getPerCodeProperty() as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $row['code'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['uses_count'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['listings_count'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['sold_listings_count'] }}</td>
                                <td class="px-4 py-2 text-right">GHS {{ number_format($row['net_revenue'], 2) }}</td>
                                <td class="px-4 py-2 text-right">GHS {{ number_format($row['estimated_commission'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No promo codes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow dark:bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 font-semibold">My codes</div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->getCodesProperty() as $c)
                    <li class="px-4 py-3 flex flex-wrap justify-between gap-2">
                        <span class="font-mono font-semibold">{{ $c->code }}</span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $c->discount_type }} · {{ $c->discount_value }}</span>
                        <span class="text-sm">Uses: {{ $c->uses_count }}@if($c->max_uses) / {{ $c->max_uses }}@endif</span>
                        <span class="text-sm {{ $c->active ? 'text-green-600' : 'text-gray-400' }}">{{ $c->active ? 'Active' : 'Inactive' }}</span>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500">No codes assigned.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-filament-panels::page>
