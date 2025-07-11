<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
	use HasFactory;
	use SoftDeletes;
	use HasUlids;

	protected $guarded = [];

	protected $casts = [
		'features' => 'array',
		'images' => 'array',
		'color' => 'array',
	];

	public function brand()
	{
		return $this->belongsTo(Brand::class);
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function brandModel()
	{
		return $this->belongsTo(BrandModel::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function country()
	{
		return $this->belongsTo(Country::class);
	}
}
