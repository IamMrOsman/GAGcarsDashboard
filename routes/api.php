<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppResourceController;
use App\Http\Controllers\Api\UserResourcesController;
use App\Http\Controllers\Api\PaystackController;
use App\Http\Controllers\Api\ItemDraftController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Controllers\vendor\Chatify\Api\MessagesController;

Route::post('/sanctum/register', [AuthController::class, 'register']);

Route::post('/sanctum/token', [AuthController::class, 'login']);

// Paystack Webhook (no auth)
Route::post('/paystack/webhook', [PaystackController::class, 'webhook']);

// OTP Routes
Route::post('/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/send-reset-password-otp', [AuthController::class, 'sendResetPasswordOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/app/countries', [AppResourceController::class, 'getCountries'])->middleware('guest:sanctum');

Route::group(['middleware' => 'auth:sanctum'], function () {
	Route::get('/user', [AuthController::class, 'user']);
	Route::post('/logout', [AuthController::class, 'logout']);
	Route::post('/logout-all', [AuthController::class, 'logoutAll']);
	Route::post('/change-password', [AuthController::class, 'changePassword']);
	Route::post('/update-profile', [AuthController::class, 'updateProfile']);
	Route::delete('/user', [AuthController::class, 'deleteAccount']);

	Route::prefix('payments/paystack')->group(function () {
		Route::get('/config', [PaystackController::class, 'config']);
		Route::post('/initialize', [PaystackController::class, 'initialize']);
		Route::post('/verify', [PaystackController::class, 'verify']);
	});

	Route::prefix('items/drafts')->group(function () {
		Route::get('/', [ItemDraftController::class, 'index']);
		Route::post('/', [ItemDraftController::class, 'store']);
		Route::get('/{item}', [ItemDraftController::class, 'show']);
		Route::patch('/{item}', [ItemDraftController::class, 'update']);
		Route::delete('/{item}', [ItemDraftController::class, 'destroy']);
		Route::post('/{item}/submit', [ItemDraftController::class, 'submit']);
	});

	Route::prefix('user/{user}')->group(function () {
		Route::get('/details', [UserResourcesController::class, 'userDetails']);
		Route::get('/listings', [UserResourcesController::class, 'userListings']);
		Route::get('/promotions', [UserResourcesController::class, 'userPromotions']);
		Route::get('/special-offers', [UserResourcesController::class, 'userSpecialOffers']);
	});

	Route::prefix('my')->group(function () {
		Route::get('/verifications', [UserResourcesController::class, 'myVerifications']);
		Route::get('/listings', [UserResourcesController::class, 'myListings']);
		Route::post('/wish-list/{item}', [UserResourcesController::class, 'addToWishList']);
		Route::get('/wish-list', [UserResourcesController::class, 'wishList']);
		Route::get('/notifications', [UserNotificationController::class, 'index']);
		Route::put('/notifications/{notification}/read', [UserNotificationController::class, 'markAsRead']);
		Route::delete('/notifications', [UserNotificationController::class, 'destroyAll']);
	});

	Route::prefix('devices')->group(function () {
		Route::post('/tokens', [DeviceTokenController::class, 'store']);
		Route::delete('/tokens', [DeviceTokenController::class, 'destroy']);
	});

	Route::prefix('app')->group(function () {
		Route::get('/similar-items-by-category/{category}/{item}', [AppResourceController::class, 'getSimilarItemsByCategory']);
		Route::get('/similar-items-by-brand/{brand}/{item}', [AppResourceController::class, 'getSimilarItemsByBrand']);
		Route::get('/similar-items-by-brand-model/{brandModel}/{item}', [AppResourceController::class, 'getSimilarItemsByBrandModel']);
		Route::get('/category-items/{category}', [AppResourceController::class, 'getCategoryItems']);
		Route::get('/brand-items/{brand}', [AppResourceController::class, 'getBrandItems']);
		Route::get('/brand-model-items/{brandModel}', [AppResourceController::class, 'getBrandModelItems']);
		Route::post('/search-items', [AppResourceController::class, 'searchItems']);
		Route::post('/can-upload', [AppResourceController::class, 'canUpload']);
		Route::get('/category-faqs/{faqCategory}', [AppResourceController::class, 'getCategoryFaqs']);
		Route::get('packages-by-category/{category}', [AppResourceController::class, 'getPackagesByCategory']);
	});

	Route::prefix('chat')->group(function () {
		Route::post('/sendMessage', [MessagesController::class, 'send']);
		Route::post('/fetchMessages', [MessagesController::class, 'fetch']);
		Route::get('/search', [MessagesController::class, 'search']);
		Route::get('/getContacts', [MessagesController::class, 'getContacts']);
		Route::post('/auth', [MessagesController::class, 'pusherAuth']);
		// Route::post('/deleteConversation', [MessagesController::class, 'deleteConversation']);
		// Route::post('/idInfo', [MessagesController::class, 'idFetchData']);
		// Route::get('/download/{fileName}', [MessagesController::class, 'download']);
		// Route::post('/makeSeen', [MessagesController::class, 'seen']);
		// Route::post('/star', [MessagesController::class, 'favorite']);
		// Route::post('/favorites', [MessagesController::class, 'getFavorites']);
		// Route::post('/shared', [MessagesController::class, 'sharedPhotos']);
		// Route::post('/updateSettings', [MessagesController::class, 'updateSettings']);
		// Route::post('/setActiveStatus', [MessagesController::class, 'setActiveStatus']);
	});
});
