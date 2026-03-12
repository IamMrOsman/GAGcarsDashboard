<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ChMessage;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChMessagePolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_ch_message');
	}

	public function view(User $user, ChMessage $chMessage): bool
	{
		return $user->can('view_ch_message');
	}

	public function create(User $user): bool
	{
		return $user->can('create_ch_message');
	}

	public function update(User $user, ChMessage $chMessage): bool
	{
		return $user->can('update_ch_message');
	}

	public function delete(User $user, ChMessage $chMessage): bool
	{
		return $user->can('delete_ch_message');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_ch_message');
	}

	public function forceDelete(User $user, ChMessage $chMessage): bool
	{
		return $user->can('force_delete_ch_message');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_ch_message');
	}

	public function restore(User $user, ChMessage $chMessage): bool
	{
		return $user->can('restore_ch_message');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_ch_message');
	}

	public function replicate(User $user, ChMessage $chMessage): bool
	{
		return $user->can('replicate_ch_message');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_ch_message');
	}
}

