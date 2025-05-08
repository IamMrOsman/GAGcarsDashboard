<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

Route::post('/sanctum/register', function (Request $request) {
	$request->validate([
		'name' => 'required|string|max:255',
		'email' => 'required|string|email|max:255|unique:users',
		'phone' => 'required|string|max:255|unique:users',
		'password' => 'required|string|min:8',
		'device_name' => 'required',
	]);

	$user = User::create([
		'name' => $request->name,
		'email' => $request->email,
		'phone' => $request->phone,
		'password' => Hash::make($request->password),
	]);

	$token = $user->createToken($request->device_name)->plainTextToken;

	return response()->json([
		'token' => $token,
		'user' => $user
	], 201);
});

Route::post('/sanctum/token', function (Request $request) {
	$request->validate([
		'email' => 'required|email',
		'password' => 'required',
		'device_name' => 'required',
	]);

	$user = User::where('email', $request->email)->first();

	if (! $user || ! Hash::check($request->password, $user->password)) {
		throw ValidationException::withMessages([
			'email' => ['The provided credentials are incorrect.'],
		]);
	}

	$token = $user->createToken($request->device_name)->plainTextToken;

	return response()->json([
		'token' => $token,
		'user' => $user
	], 200);
});

Route::get('/user', function (Request $request) {
	return $request->user();
})->middleware('auth:sanctum');
