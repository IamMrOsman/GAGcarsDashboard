<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tzsk\Otp\Facades\Otp;

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
			'phone' => 'nullable|string|max:255|unique:users',
			'password' => 'required|string|min:8',
			'device_name' => 'nullable',
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
	 * Send OTP to user's phone and email
	 */
	public function sendOtp(Request $request)
	{
		$request->validate([
			'phone' => 'required|string',
			'email' => 'nullable|email',
		]);

		$user = User::where('phone', $request->phone)
			// ->orWhere('email', $request->email)
			->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'phone' => ['User not found with provided phone or email.'],
			]);
		}

		// Generate OTP for phone
		$phoneOtp = Otp::generate($user->phone);

		// Generate OTP for email
		// $emailOtp = Otp::generate($user->email);

		// Send SMS via Arkesel
		$smsDriver = new \App\Services\Sms\ArkeselSmsDriver();
		$smsDriver->send($user->phone, "Your OTP is: {$phoneOtp}");

		// Send Email using Laravel's built-in mail
		// \Mail::raw("Your OTP is: {$emailOtp}", function ($message) use ($user) {
		// 	$message->to($user->email)
		// 		->subject('Your OTP Code');
		// });

		return response()->json([
			'message' => 'OTP sent successfully to phone',
			'phone' => $user->phone,
			'email' => $user->email
		], 200);
	}

	/**
	 * Verify OTP and return authentication token
	 */
	public function verifyOtp(Request $request)
	{
		$request->validate([
			'phone' => 'required|string',
			'otp' => 'required|string|size:6',
			'device_name' => 'nullable',
		]);

		$user = User::where('phone', $request->phone)->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'phone' => ['User not found.'],
			]);
		}

		// Verify OTP for phone
		if (!Otp::match($request->otp, $user->phone)) {
			// Also try to verify with email OTP
			if (!Otp::match($request->otp, $user->email)) {
				throw ValidationException::withMessages([
					'otp' => ['Invalid OTP.'],
				]);
			}
		}

		// Generate token
		$token = $user->createToken($request->device_name)->plainTextToken;

		return response()->json([
			'message' => 'OTP verified successfully',
			'token' => $token,
			'user' => $user
		], 200);
	}

	/**
	 * Get authenticated user details
	 */
	public function user(Request $request)
	{
		return $request->user();
	}
}
