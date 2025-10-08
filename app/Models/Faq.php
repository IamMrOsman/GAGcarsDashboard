<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Faq extends Model
{
    use HasUlids;

    protected $guarded = [];

	protected $casts = [
		'tags' => 'array',
	];

	public function category()
	{
		return $this->belongsTo(FaqCategory::class, 'category_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
