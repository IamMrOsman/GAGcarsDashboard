<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BrandModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandModelPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_brand_model');
	}

	public function view(User $user, BrandModel $brandModel): bool
	{
		return $user->can('view_brand_model');
	}

	public function create(User $user): bool
	{
		return $user->can('create_brand_model');
	}

	public function update(User $user, BrandModel $brandModel): bool
	{
		return $user->can('update_brand_model');
	}

	public function delete(User $user, BrandModel $brandModel): bool
	{
		return $user->can('delete_brand_model');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_brand_model');
	}

	public function forceDelete(User $user, BrandModel $brandModel): bool
	{
		return $user->can('force_delete_brand_model');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_brand_model');
	}

	public function restore(User $user, BrandModel $brandModel): bool
	{
		return $user->can('restore_brand_model');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_brand_model');
	}

	public function replicate(User $user, BrandModel $brandModel): bool
	{
		return $user->can('replicate_brand_model');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_brand_model');
	}
}

