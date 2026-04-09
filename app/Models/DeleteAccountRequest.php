<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class DeleteAccountRequest extends Model
{
	use HasUlids;

	protected $guarded = [];

	public $incrementing = false;

	protected $keyType = 'string';

	protected $casts = [
		'snapshot' => 'array',
		'requested_at' => 'datetime',
		'reviewed_at' => 'datetime',
	];
}

