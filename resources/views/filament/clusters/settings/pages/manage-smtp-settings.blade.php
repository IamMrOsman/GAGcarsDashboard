<x-filament-panels::page>
	{{ $this->form }}

	<x-filament-actions::modals />

	<div class="flex gap-4 mt-6">
		<x-filament::button
			wire:click="save"
		>
			Save SMTP Settings
		</x-filament::button>
	</div>
</x-filament-panels::page>
