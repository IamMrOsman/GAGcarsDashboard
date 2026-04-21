<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Public storage base URL (optional)
	|--------------------------------------------------------------------------
	|
	| If files are served from a different host than APP_URL (e.g. API on
	| api.example.com but images on cdn.example.com), set this to the origin
	| that should prefix /storage/... URLs (no trailing slash), e.g.:
	|   PUBLIC_STORAGE_BASE_URL=https://dashboard.gagcars.com
	|
	*/
	'public_storage_base_url' => env('PUBLIC_STORAGE_BASE_URL'),

];
