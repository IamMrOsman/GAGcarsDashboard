<x-filament-widgets::widget>
	<x-filament::section>
		<div class="space-y-4">
			<!-- Listing Approval Settings -->
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
				<div class="flex items-center space-x-2">
					<x-filament::icon
						name="heroicon-o-check-circle"
						class="h-5 w-5 text-primary-500"
					/>
					<span class="text-sm font-medium text-gray-900">Listing Approval By Country:</span>
				</div>
				<div class="flex flex-wrap gap-2">
					@foreach($approvalSettings as $target => $enabled)
						<x-filament::badge :color="$enabled ? 'success' : 'danger'">
							{{ $target }}: {{ $enabled ? 'Enabled' : 'Disabled' }}
						</x-filament::badge>
					@endforeach
				</div>
			</div>

			<!-- Listing Approval by Category -->
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
				<div class="flex items-center space-x-2">
					<x-filament::icon
						name="heroicon-o-folder"
						class="h-5 w-5 text-primary-500"
					/>
					<span class="text-sm font-medium text-gray-900">Listing Approval by Category:</span>
				</div>
				<div class="flex flex-wrap gap-2">
					@foreach($approvalSettingsByCategory as $category => $enabled)
						<x-filament::badge :color="$enabled ? 'success' : 'danger'">
							{{ $category }}: {{ $enabled ? 'Enabled' : 'Disabled' }}
						</x-filament::badge>
					@endforeach
				</div>
			</div>

			<!-- Payment Requirement Settings -->
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
				<div class="flex items-center space-x-2">
					<x-filament::icon
						name="heroicon-o-credit-card"
						class="h-5 w-5 text-warning-500"
					/>
					<span class="text-sm font-medium text-gray-900">Payment Requirement By Country:</span>
				</div>
				<div class="flex flex-wrap gap-2">
					@foreach($paymentSettings as $target => $enabled)
						<x-filament::badge :color="$enabled ? 'success' : 'danger'">
							{{ $target }}: {{ $enabled ? 'Enabled' : 'Disabled' }}
						</x-filament::badge>
					@endforeach
				</div>
			</div>

			<!-- Payment Requirement by Category -->
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
				<div class="flex items-center space-x-2">
					<x-filament::icon
						name="heroicon-o-rectangle-stack"
						class="h-5 w-5 text-warning-500"
					/>
					<span class="text-sm font-medium text-gray-900">Payment Requirement by Category:</span>
				</div>
				<div class="flex flex-wrap gap-2">
					@foreach($paymentSettingsByCategory as $category => $enabled)
						<x-filament::badge :color="$enabled ? 'success' : 'danger'">
							{{ $category }}: {{ $enabled ? 'Enabled' : 'Disabled' }}
						</x-filament::badge>
					@endforeach
				</div>
			</div>
		</div>
	</x-filament::section>
</x-filament-widgets::widget>
