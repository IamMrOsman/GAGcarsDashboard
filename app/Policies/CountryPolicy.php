<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Country;
use Illuminate\Auth\Access\HandlesAuthorization;

class CountryPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_country');
	}

	public function view(User $user, Country $country): bool
	{
        return $user->can('view_country');
	}

	public function create(User $user): bool
	{
		return $user->can('create_country');
	}

	public function update(User $user, Country $country): bool
	{
		return $user->can('update_country');
	}

	public function delete(User $user, Country $country): bool
	{
		return $user->can('delete_country');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_country');
	}

	public function forceDelete(User $user, Country $country): bool
	{
		return $user->can('force_delete_country');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_country');
	}

	public function restore(User $user, Country $country): bool
	{
		return $user->can('restore_country');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_country');
	}

	public function replicate(User $user, Country $country): bool
	{
		return $user->can('replicate_country');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_country');
	}
}

