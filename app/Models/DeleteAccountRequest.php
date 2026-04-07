<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeleteAccountRequest extends Model
{
	protected $guarded = [];

	protected $casts = [
		'snapshot' => 'array',
		'requested_at' => 'datetime',
		'reviewed_at' => 'datetime',
	];
}

