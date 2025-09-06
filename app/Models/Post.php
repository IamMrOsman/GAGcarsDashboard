<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Post extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

	public function country()
	{
		return $this->belongsTo(Country::class);
	}

	public function category()
	{
		return $this->belongsTo(PostCategory::class);
	}

	protected $casts = [
		'tags' => 'array',
	];
}
