<?php

namespace App\Policies;

use App\Models\User;
use App\Models\State;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatePolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_state');
	}

	public function view(User $user, State $state): bool
	{
		return $user->can('view_state');
	}

	public function create(User $user): bool
	{
		return $user->can('create_state');
	}

	public function update(User $user, State $state): bool
	{
		return $user->can('update_state');
	}

	public function delete(User $user, State $state): bool
	{
		return $user->can('delete_state');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_state');
	}

	public function forceDelete(User $user, State $state): bool
	{
		return $user->can('force_delete_state');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_state');
	}

	public function restore(User $user, State $state): bool
	{
		return $user->can('restore_state');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_state');
	}

	public function replicate(User $user, State $state): bool
	{
		return $user->can('replicate_state');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_state');
	}
}

