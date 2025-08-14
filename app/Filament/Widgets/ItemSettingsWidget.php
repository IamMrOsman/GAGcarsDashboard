<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\Setting;
use Filament\Widgets\Widget;

class ItemSettingsWidget extends Widget
{
	protected static string $view = 'filament.widgets.item-settings-widget';

	protected int | string | array $columnSpan = 'full';

	public function getViewData(): array
	{
		$approvalSettings = [];
		$paymentSettings = [];

		// Get global settings
		$globalApproval = Setting::where('key_slug', 'require_listing_approval_for_all')->first();
		$globalPayment = Setting::where('key_slug', 'require_payment_for_all')->first();

		$approvalSettings['Global'] = $globalApproval ? $globalApproval->value === 'true' : false;
		$paymentSettings['Global'] = $globalPayment ? $globalPayment->value === 'true' : false;

		// Get country-specific settings
		$countries = Country::all();
		foreach ($countries as $country) {
			$approvalKey = "require_listing_approval_for_" . strtolower($country->name);
			$paymentKey = "require_payment_for_" . strtolower($country->name);

			$approvalSetting = Setting::where('key_slug', $approvalKey)->first();
			$paymentSetting = Setting::where('key_slug', $paymentKey)->first();

			$approvalSettings[$country->name] = $approvalSetting ? $approvalSetting->value === 'true' : false;
			$paymentSettings[$country->name] = $paymentSetting ? $paymentSetting->value === 'true' : false;
		}

		return [
			'approvalSettings' => $approvalSettings,
			'paymentSettings' => $paymentSettings,
		];
	}
}
