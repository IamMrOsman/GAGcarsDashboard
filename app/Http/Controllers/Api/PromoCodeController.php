<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'package_id' => ['nullable', 'string', 'exists:packages,id'],
        ]);

        if (! empty($data['package_id'])) {
            $package = Package::query()->findOrFail($data['package_id']);
            $result = PromoCodeService::tryQuote($data['code'], $package);
        } else {
            $result = PromoCodeService::tryDraftValidate($data['code']);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }
}
