<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ChFavorite;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChFavoritePolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_ch_favorite');
	}

	public function view(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('view_ch_favorite');
	}

	public function create(User $user): bool
	{
		return $user->can('create_ch_favorite');
	}

	public function update(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('update_ch_favorite');
	}

	public function delete(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('delete_ch_favorite');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_ch_favorite');
	}

	public function forceDelete(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('force_delete_ch_favorite');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_ch_favorite');
	}

	public function restore(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('restore_ch_favorite');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_ch_favorite');
	}

	public function replicate(User $user, ChFavorite $chFavorite): bool
	{
		return $user->can('replicate_ch_favorite');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_ch_favorite');
	}
}

