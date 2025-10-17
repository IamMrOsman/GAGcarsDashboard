<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryRequirement extends Model
{
	protected $fillable = [
		'country_id',
		'category_id',
		'require_approval',
		'require_payment',
	];

	protected $casts = [
		'require_approval' => 'boolean',
		'require_payment' => 'boolean',
	];

	public function country()
	{
		return $this->belongsTo(Country::class);
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}
}
