<?php

use Illuminate\Support\Facades\Broadcast;

// Chatify private channel: private-chatify.{id}
Broadcast::channel('chatify.{id}', function ($user, $id) {
	return (string) $user->id === (string) $id;
});

