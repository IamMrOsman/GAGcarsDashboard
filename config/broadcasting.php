<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Broadcaster
	|--------------------------------------------------------------------------
	*/
	'default' => env('BROADCAST_CONNECTION', 'pusher'),

	/*
	|--------------------------------------------------------------------------
	| Broadcast Connections
	|--------------------------------------------------------------------------
	*/
	'connections' => [
		'pusher' => [
			'driver' => 'pusher',
			'key' => env('PUSHER_APP_KEY'),
			'secret' => env('PUSHER_APP_SECRET'),
			'app_id' => env('PUSHER_APP_ID'),
			'options' => [
				'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
				'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
				'host' => env('PUSHER_HOST'),
				'port' => env('PUSHER_PORT'),
				'scheme' => env('PUSHER_SCHEME', 'https'),
			],
		],

		'log' => [
			'driver' => 'log',
		],

		'null' => [
			'driver' => 'null',
		],
	],

];

