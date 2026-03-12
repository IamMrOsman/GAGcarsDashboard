<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WishList;
use Illuminate\Auth\Access\HandlesAuthorization;

class WishListPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_wish_list');
	}

	public function view(User $user, WishList $wishList): bool
	{
		return $user->can('view_wish_list');
	}

	public function create(User $user): bool
	{
		return $user->can('create_wish_list');
	}

	public function update(User $user, WishList $wishList): bool
	{
		return $user->can('update_wish_list');
	}

	public function delete(User $user, WishList $wishList): bool
	{
		return $user->can('delete_wish_list');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_wish_list');
	}

	public function forceDelete(User $user, WishList $wishList): bool
	{
		return $user->can('force_delete_wish_list');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_wish_list');
	}

	public function restore(User $user, WishList $wishList): bool
	{
		return $user->can('restore_wish_list');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_wish_list');
	}

	public function replicate(User $user, WishList $wishList): bool
	{
		return $user->can('replicate_wish_list');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_wish_list');
	}
}

