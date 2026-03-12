<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Broadcast;
use Illuminate\Auth\Access\HandlesAuthorization;

class BroadcastPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_broadcast');
	}

	public function view(User $user, Broadcast $broadcast): bool
	{
		return $user->can('view_broadcast');
	}

	public function create(User $user): bool
	{
		return $user->can('create_broadcast');
	}

	public function update(User $user, Broadcast $broadcast): bool
	{
		return $user->can('update_broadcast');
	}

	public function delete(User $user, Broadcast $broadcast): bool
	{
		return $user->can('delete_broadcast');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_broadcast');
	}

	public function forceDelete(User $user, Broadcast $broadcast): bool
	{
		return $user->can('force_delete_broadcast');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_broadcast');
	}

	public function restore(User $user, Broadcast $broadcast): bool
	{
		return $user->can('restore_broadcast');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_broadcast');
	}

	public function replicate(User $user, Broadcast $broadcast): bool
	{
		return $user->can('replicate_broadcast');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_broadcast');
	}
}

