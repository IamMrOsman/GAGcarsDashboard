<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

	protected $table = 'ch_messages';

	public function from()
	{
		return $this->belongsTo(User::class, 'from_id');
	}

	public function to()
	{
		return $this->belongsTo(User::class, 'to_id');
	}
}
