<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    /** @use HasFactory<\Database\Factories\VerificationFactory> */
    use HasFactory;

	protected $guarded = [];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function verifiedBy()
	{
		return $this->belongsTo(User::class, 'verified_by');
	}

	public function rejectedBy()
	{
		return $this->belongsTo(User::class, 'rejected_by');
	}
}
