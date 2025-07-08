<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasUlids;

    protected $guarded = [];

	public function country(): BelongsTo
	{
		return $this->belongsTo(Country::class);
	}
}
