<div class="rounded-2xl p-4 sm:p-6 bg-gradient-to-r from-primary-50 via-white to-primary-50 ring-1 ring-gray-200/70 dark:from-primary-950/30 dark:via-gray-950 dark:to-primary-950/20 dark:ring-gray-800">
	<div class="space-y-4">
		<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
			<div class="flex flex-wrap justify-center gap-2 lg:justify-start">
			@php
				$tabs = [
					'today' => 'Today',
					'7_days' => '7 Days',
					'30_days' => '30 Days',
					'90_days' => '90 Days',
					'all_time' => 'All',
				];
			@endphp

			@foreach ($tabs as $key => $label)
				<button
					type="button"
					wire:click="$set('period', '{{ $key }}')"
					class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium transition
						{{ $period === $key
							? 'bg-primary-600 text-white shadow-sm'
							: 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700' }}"
				>
					{{ $label }}
				</button>
			@endforeach
		</div>

			<div class="w-full sm:w-72 lg:w-64">
				<label class="sr-only" for="walletKpiCountry">Country</label>
				<select
					id="walletKpiCountry"
					wire:model.live="countryId"
					class="w-full rounded-lg border-gray-200 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
				>
					<option value="all">All countries</option>
					@foreach ($countries as $c)
						<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
					@endforeach
				</select>
			</div>
		</div>
	</div>

	@if ($loadError)
		<div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-800/40 dark:bg-danger-950/30 dark:text-danger-200">
			<div class="flex items-center justify-between gap-3">
				<div>{{ $loadError }}</div>
				<x-filament::button size="sm" color="danger" wire:click="loadKpis">
					Retry
				</x-filament::button>
			</div>
		</div>
	@endif

	<div class="relative">
		<div
			wire:loading.delay
			class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/60 dark:bg-gray-950/60"
		>
			<div class="text-sm text-gray-700 dark:text-gray-200">Loading…</div>
		</div>

		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			@php
				$cards = [
					['title' => 'Total Balance', 'key' => 'total_balance', 'type' => 'money'],
					['title' => 'Total Top-ups', 'key' => 'total_topups', 'type' => 'money'],
					['title' => 'Total Spending', 'key' => 'total_spending', 'type' => 'money'],
					['title' => 'Listing Spending', 'key' => 'listing_spending', 'type' => 'money'],
					['title' => 'Promotion Spending', 'key' => 'promotion_spending', 'type' => 'money'],
					['title' => 'Revenue', 'key' => 'revenue', 'type' => 'money'],
					['title' => 'Failed Transactions', 'key' => 'failed_transactions', 'type' => 'count'],
					['title' => 'Pending Transactions', 'key' => 'pending_transactions', 'type' => 'count'],
				];
			@endphp

			@foreach ($cards as $card)
				<x-filament::card>
					<div class="flex items-start justify-between gap-3">
						<div class="text-sm font-medium text-gray-600 dark:text-gray-300">
							{{ $card['title'] }}
						</div>
					</div>

					<div class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
						@php($raw = $kpis[$card['key']] ?? ($card['type'] === 'count' ? 0 : 0.0))

						@if ($card['type'] === 'money')
							{{ $this->formatMoney((float) $raw) }}
						@else
							{{ number_format((int) $raw) }}
						@endif
					</div>
				</x-filament::card>
			@endforeach
		</div>
	</div>
</div>

