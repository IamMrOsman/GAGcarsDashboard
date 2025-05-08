<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandModel extends Model
{
    use HasFactory;

	protected $guarded = [];

	public function brand()
	{
		return $this->belongsTo(Brand::class);
	}

	public function items()
	{
		return $this->hasMany(Item::class);
	}
}
