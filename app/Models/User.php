<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
	/** @use HasFactory<\Database\Factories\UserFactory> */
	use HasFactory, Notifiable, HasUlids, SoftDeletes, HasApiTokens, HasRoles, TwoFactorAuthenticatable;

	protected $casts = [
		'uploads_left' => 'array',
	];

	public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo ?? 'https://ui-avatars.com/api/?name=' . $this->name . '&color=FFFFFF&background=09090b';
    }

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
		return (
			in_array($this->email, ['mcjohnsonlyndon@gmail.com', '1kwakubonsam@gmail.com']) ||
			$this->hasRole('admin') ||
			$this->roles()->exists()
		);
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

	/**
	 * Normalize uploads_left to an array (handles legacy integer or null).
	 */
	protected function getUploadsLeftArray(): array
	{
		$raw = $this->uploads_left;
		if (is_array($raw)) {
			return $raw;
		}
		if (is_numeric($raw)) {
			return ['all' => (int) $raw];
		}
		return [];
	}

	/**
	 * Get uploads left for a category (from uploads_left JSON: category_id => count).
	 * Checks category key first, then 'all' for packages not tied to a category.
	 */
	public function getUploadsLeftForCategory($categoryId): int
	{
		$uploads = $this->getUploadsLeftArray();
		$key = $categoryId !== null ? (string) $categoryId : 'all';
		return (int) ($uploads[$key] ?? $uploads['all'] ?? 0);
	}

	/**
	 * Add uploads for a category. If category key exists, add to it; otherwise set it.
	 */
	public function addUploadsForCategory($categoryId, int $amount): void
	{
		$uploads = $this->getUploadsLeftArray();
		$key = $categoryId !== null ? (string) $categoryId : 'all';
		$current = (int) ($uploads[$key] ?? 0);
		$uploads[$key] = $current + $amount;
		$this->update(['uploads_left' => $uploads]);
	}

	/**
	 * Decrement uploads for a category by one. No-op if count for category is 0 or missing.
	 */
	public function decrementUploadsForCategory($categoryId): void
	{
		$uploads = $this->getUploadsLeftArray();
		$key = $categoryId !== null ? (string) $categoryId : 'all';
		$current = (int) ($uploads[$key] ?? 0);
		if ($current <= 0) {
			return;
		}
		$uploads[$key] = $current - 1;
		$this->update(['uploads_left' => $uploads]);
	}
}
