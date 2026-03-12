<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PostCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostCategoryPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_post_category');
	}

	public function view(User $user, PostCategory $postCategory): bool
	{
		return $user->can('view_post_category');
	}

	public function create(User $user): bool
	{
		return $user->can('create_post_category');
	}

	public function update(User $user, PostCategory $postCategory): bool
	{
		return $user->can('update_post_category');
	}

	public function delete(User $user, PostCategory $postCategory): bool
	{
		return $user->can('delete_post_category');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_post_category');
	}

	public function forceDelete(User $user, PostCategory $postCategory): bool
	{
		return $user->can('force_delete_post_category');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_post_category');
	}

	public function restore(User $user, PostCategory $postCategory): bool
	{
		return $user->can('restore_post_category');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_post_category');
	}

	public function replicate(User $user, PostCategory $postCategory): bool
	{
		return $user->can('replicate_post_category');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_post_category');
	}
}

