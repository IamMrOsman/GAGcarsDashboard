<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SpecialOffer;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpecialOfferPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('view_any_special_offer');
	}

	public function view(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('view_special_offer');
	}

	public function create(User $user): bool
	{
		return $user->can('create_special_offer');
	}

	public function update(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('update_special_offer');
	}

	public function delete(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('delete_special_offer');
	}

	public function deleteAny(User $user): bool
	{
		return $user->can('delete_any_special_offer');
	}

	public function forceDelete(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('force_delete_special_offer');
	}

	public function forceDeleteAny(User $user): bool
	{
		return $user->can('force_delete_any_special_offer');
	}

	public function restore(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('restore_special_offer');
	}

	public function restoreAny(User $user): bool
	{
		return $user->can('restore_any_special_offer');
	}

	public function replicate(User $user, SpecialOffer $specialOffer): bool
	{
		return $user->can('replicate_special_offer');
	}

	public function reorder(User $user): bool
	{
		return $user->can('reorder_special_offer');
	}
}

