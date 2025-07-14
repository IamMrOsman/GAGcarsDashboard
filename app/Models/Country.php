<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
	protected $guarded = [];

    public function states()
	{
		return $this->hasMany(State::class);
	}

	public function items()
	{
		return $this->hasMany(Item::class);
	}

	public function packages()
	{
		return $this->hasMany(Package::class);
	}

	public function users()
	{
		return $this->hasMany(User::class);
	}

	public function specialOffers()
	{
		return $this->hasMany(SpecialOffer::class);
	}
}
