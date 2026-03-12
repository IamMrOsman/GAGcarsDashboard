<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Brand;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_brand');
	}

	public function view(User $user, Brand $brand): bool
	{
		return $user->can('view_brand');
	}

	public function create(User $user): bool
	{
		return $user->can('create_brand');
	}

	public function update(User $user, Brand $brand): bool
	{
		return $user->can('update_brand');
	}

	public function delete(User $user, Brand $brand): bool
	{
		return $user->can('delete_brand');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_brand');
	}

	public function forceDelete(User $user, Brand $brand): bool
	{
		return $user->can('force_delete_brand');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_brand');
	}

	public function restore(User $user, Brand $brand): bool
	{
		return $user->can('restore_brand');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_brand');
	}

	public function replicate(User $user, Brand $brand): bool
	{
		return $user->can('replicate_brand');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_brand');
	}
}

