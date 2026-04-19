<?php

namespace App\Services;

use App\Models\CategoryRequirement;
use App\Models\Package;

/**
 * Upload credits (users.uploads_left) apply when either category rules require payment
 * or an upload package exists for that category (dashboard-assigned packages).
 */
final class UploadCreditPolicy
{
	public static function paidUploadApplies(int|string|null $categoryId, ?int $countryId): bool
	{
		if ($categoryId === null || $categoryId === '') {
			return false;
		}

		$cid = (int) $categoryId;

		if ($countryId !== null) {
			if (CategoryRequirement::query()
				->where('category_id', $cid)
				->where('country_id', $countryId)
				->where('require_payment', true)
				->exists()) {
				return true;
			}
		} else {
			if (CategoryRequirement::query()
				->where('category_id', $cid)
				->where('require_payment', true)
				->exists()) {
				return true;
			}
		}

		$query = Package::query()
			->where('package_type', 'upload')
			->where('category_id', $cid);

		if ($countryId !== null) {
			$query->where(function ($q) use ($countryId): void {
				$q->whereNull('country_id')
					->orWhere('country_id', $countryId);
			});
		}

		return $query->exists();
	}
}
