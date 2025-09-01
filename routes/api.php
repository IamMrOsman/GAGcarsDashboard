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
Route::post('/send-reset-password-otp', [AuthController::class, 'sendResetPasswordOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::group(['middleware' => 'auth:sanctum'], function () {
	Route::get('/user', [AuthController::class, 'user']);
	Route::post('/logout', [AuthController::class, 'logout']);
	Route::post('/logout-all', [AuthController::class, 'logoutAll']);
	Route::post('/change-password', [AuthController::class, 'changePassword']);
	Route::post('/update-profile', [AuthController::class, 'updateProfile']);

	Route::prefix('user/{user}')->group(function () {
		Route::get('/listings', [UserResourcesController::class, 'userListings']);
		Route::get('/promotions', [UserResourcesController::class, 'userPromotions']);
		Route::get('/special-offers', [UserResourcesController::class, 'userSpecialOffers']);
	});

	Route::prefix('my')->group(function () {
		Route::get('/verifications', [UserResourcesController::class, 'myVerifications']);
		Route::get('/listings', [UserResourcesController::class, 'myListings']);
		Route::post('/wish-list/{item}', [UserResourcesController::class, 'addToWishList']);
		Route::get('/wish-list', [UserResourcesController::class, 'wishList']);
	});
});
