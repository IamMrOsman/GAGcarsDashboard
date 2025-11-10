<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Notifications\Notification;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements FilamentUser
{
	/** @use HasFactory<\Database\Factories\UserFactory> */
	use HasFactory, Notifiable, HasUlids, SoftDeletes, HasApiTokens, HasRoles;

	/**
	 * Boot the model.
	 */
	protected static function boot()
	{
		parent::boot();

		static::deleting(function ($user) {
			$userId = $user->getKey();

			// Manually delete role and permission relationships with proper ULID handling
			$tableNames = config('permission.table_names');
			$columnNames = config('permission.column_names');
			$modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

			// Delete from model_has_roles
			DB::table($tableNames['model_has_roles'])
				->where($modelMorphKey, $userId)
				->where('model_type', static::class)
				->delete();

			// Delete from model_has_permissions
			DB::table($tableNames['model_has_permissions'])
				->where($modelMorphKey, $userId)
				->where('model_type', static::class)
				->delete();

			// Delete messages where user is sender or receiver
			DB::table('ch_messages')
				->where(function ($query) use ($userId) {
					$query->where('from_id', $userId)
						->orWhere('to_id', $userId);
				})
				->delete();

			// Delete chat favorites
			DB::table('ch_favorites')
				->where('user_id', $userId)
				->delete();

			// Delete posts (if no cascade)
			DB::table('posts')
				->where('user_id', $userId)
				->delete();

			// Delete broadcasts (if no cascade)
			DB::table('broadcasts')
				->where('user_id', $userId)
				->delete();

			// Delete FAQs (if no cascade)
			DB::table('faqs')
				->where('user_id', $userId)
				->delete();
		});
	}

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $guarded = [];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var list<string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
		];
	}

	/**
	 * Get the data type of the primary key.
	 *
	 * @return string
	 */
	public function getKeyType()
	{
		return 'string';
	}

	/**
	 * Get the user's initials
	 */
	public function initials(): string
	{
		return Str::of($this->name)
			->explode(' ')
			->map(fn(string $name) => Str::of($name)->substr(0, 1))
			->implode('');
	}

	public function items()
	{
		return $this->hasMany(Item::class);
	}

	public function country()
	{
		return $this->belongsTo(Country::class);
	}

	public function state()
	{
		return $this->belongsTo(State::class);
	}

	public function canAccessPanel(Panel $panel): bool
	{
		return true;
	}

	public function verifications()
	{
		return $this->hasMany(Verification::class);
	}

	public function sentMessages()
	{
		return $this->hasMany(Message::class, 'from_id', 'id');
	}

	public function receivedMessages()
	{
		return $this->hasMany(Message::class, 'to_id', 'id');
	}

	public function isVerified(): bool
	{
		return $this->verifications()->where('status', 'verified')->where('verification_type', 'individual')->exists();
	}

	public function hasVerification()
	{
		return $this->verifications()->where('verification_type', 'individual')->exists();
	}

	public function isVerifiedDealer(): bool
	{
		return $this->verifications()->where('status', 'verified')->where('verification_type', 'dealer')->exists();
	}

	public function hasDealerVerification()
	{
		return $this->verifications()->where('verification_type', 'dealer')->exists();
	}

	public function promotions()
	{
		return $this->hasMany(Promotion::class);
	}

	public function specialOffers()
	{
		return $this->hasMany(SpecialOffer::class);
	}

	public function wishList()
	{
		return $this->hasMany(WishList::class);
	}
}
