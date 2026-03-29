<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
	use HasUlids;

	protected $guarded = [];

	protected $casts = [
		'metadata' => 'array',
		'gateway_response' => 'array',
		'paid_at' => 'datetime',
		'fulfilled_at' => 'datetime',
	];

	public function package()
	{
		return $this->belongsTo(Package::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function item()
	{
		return $this->belongsTo(Item::class);
	}
}
