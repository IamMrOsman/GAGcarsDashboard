<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Listing lifetime (fallback when dashboard setting is unset)
	|--------------------------------------------------------------------------
	|
	| Primary source is Settings → Listing expiry in the admin panel (settings
	| table, key_slug listing). This value is used when no row exists yet.
	|
	*/
	'listing_active_days' => (int) env('LISTING_ACTIVE_DAYS', 30),

];
