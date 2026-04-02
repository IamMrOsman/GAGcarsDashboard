<x-filament-panels::page>
	<div class="space-y-6">
		@livewire(\App\Filament\Widgets\WalletKpisOverview::class)

		@livewire(\App\Filament\Widgets\WalletTransactionsTable::class)
	</div>
</x-filament-panels::page>

