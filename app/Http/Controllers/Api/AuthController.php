<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
	/**
	 * Sanctum Register
	 */
	public function register(Request $request)
	{
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
	}

	/**
	 * Sanctum Login
	 */
	public function login(Request $request)
	{
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
	}

	/**
	 * User Details
	 */
	public function user(Request $request)
	{
		return $request->user();
	}
}
