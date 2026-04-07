<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedUserArchive extends Model
{
	protected $guarded = [];

	protected $casts = [
		'payload' => 'array',
		'archived_at' => 'datetime',
	];
}

