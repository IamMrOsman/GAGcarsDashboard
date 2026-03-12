<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ItemField;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemFieldPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_item_field');
	}

	public function view(User $user, ItemField $itemField): bool
	{
		return $user->can('view_item_field');
	}

	public function create(User $user): bool
	{
		return $user->can('create_item_field');
	}

	public function update(User $user, ItemField $itemField): bool
	{
		return $user->can('update_item_field');
	}

	public function delete(User $user, ItemField $itemField): bool
	{
		return $user->can('delete_item_field');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_item_field');
	}

	public function forceDelete(User $user, ItemField $itemField): bool
	{
		return $user->can('force_delete_item_field');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_item_field');
	}

	public function restore(User $user, ItemField $itemField): bool
	{
		return $user->can('restore_item_field');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_item_field');
	}

	public function replicate(User $user, ItemField $itemField): bool
	{
		return $user->can('replicate_item_field');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_item_field');
	}
}

