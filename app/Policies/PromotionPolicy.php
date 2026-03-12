<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Promotion;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_promotion');
	}

	public function view(User $user, Promotion $promotion): bool
	{
		return $user->can('view_promotion');
	}

	public function create(User $user): bool
	{
		return $user->can('create_promotion');
	}

	public function update(User $user, Promotion $promotion): bool
	{
		return $user->can('update_promotion');
	}

	public function delete(User $user, Promotion $promotion): bool
	{
		return $user->can('delete_promotion');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_promotion');
	}

	public function forceDelete(User $user, Promotion $promotion): bool
	{
		return $user->can('force_delete_promotion');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_promotion');
	}

	public function restore(User $user, Promotion $promotion): bool
	{
		return $user->can('restore_promotion');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_promotion');
	}

	public function replicate(User $user, Promotion $promotion): bool
	{
		return $user->can('replicate_promotion');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_promotion');
	}
}

