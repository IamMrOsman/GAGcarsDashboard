<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserResourcesController;
use App\Http\Controllers\Api\AuthController;

Route::post('/sanctum/register', [AuthController::class, 'register']);

Route::post('/sanctum/token', [AuthController::class, 'login']);

// OTP Routes
Route::post('/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);

Route::group(['middleware' => 'auth:sanctum'], function () {
	Route::get('/user', [AuthController::class, 'user']);

	Route::prefix('my')->group(function () {
		Route::get('/listings', [UserResourcesController::class, 'myListings']);
		Route::get('/verifications', [UserResourcesController::class, 'myVerifications']);
		Route::get('/promotions', [UserResourcesController::class, 'myPromotions']);
		Route::get('/special-offers', [UserResourcesController::class, 'mySpecialOffers']);
	});
});
