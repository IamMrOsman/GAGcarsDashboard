<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Package;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackagePolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_package');
	}

	public function view(User $user, Package $package): bool
	{
		return $user->can('view_package');
	}

	public function create(User $user): bool
	{
		return $user->can('create_package');
	}

	public function update(User $user, Package $package): bool
	{
		return $user->can('update_package');
	}

	public function delete(User $user, Package $package): bool
	{
		return $user->can('delete_package');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_package');
	}

	public function forceDelete(User $user, Package $package): bool
	{
		return $user->can('force_delete_package');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_package');
	}

	public function restore(User $user, Package $package): bool
	{
		return $user->can('restore_package');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_package');
	}

	public function replicate(User $user, Package $package): bool
	{
		return $user->can('replicate_package');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_package');
	}
}

