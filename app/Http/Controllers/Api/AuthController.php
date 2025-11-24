<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Tzsk\Otp\Facades\Otp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\SmtpSettingsService;
use Illuminate\Support\Facades\Config;
use App\Mail\OtpMail;

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
			'country_id' => 'nullable|exists:countries,id',
			'state_id' => 'nullable|exists:states,id',
		]);

		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'phone' => $request->phone,
			'password' => Hash::make($request->password),
			'country_id' => $request->country_id,
			'state_id' => $request->state_id,
		]);

		$token = $user->createToken($request->phone)->plainTextToken;

		return response()->json([
			// 'token' => $token,
			'user' => $user->load('country', 'state'),
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

		$token = $user->createToken($request->email)->plainTextToken;

		return response()->json([
			'token' => $token,
			'user' => $user->load('country', 'state'),
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
			->orWhere('email', $request->email)
			->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'phone' => ['User not found with provided phone or email.'],
			]);
		}

		// Generate OTP for phone
		$phoneOtp = Otp::generate($user->phone);

		// Generate OTP for email
		$emailOtp = Otp::generate($user->email);

		// Send SMS via Arkesel
		$smsDriver = new \App\Services\Sms\ArkeselSmsDriver();
		$smsDriver->send($user->phone, "Your OTP is: {$phoneOtp}");

		// Send Email using Laravel's built-in mail
		// \Mail::raw("Your OTP is: {$emailOtp}", function ($message) use ($user) {
		// 	$message->to($user->email)
		// 		->subject('Your OTP Code');
		// });

		$this->sendEmailWithSmtpSettings($user->email, $emailOtp, $user->name);

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
		$token = $user->createToken($request->phone)->plainTextToken;

		return response()->json([
			'message' => 'OTP verified successfully',
			'token' => $token,
			'user' => $user->load('country', 'state'),
		], 200);
	}

	/**
	 * Get authenticated user details
	 */
	public function user(Request $request)
	{
		$user = $request->user();

		return response()->json([
			'user' => $user->load('country', 'state'),
			'verified' => $user->isVerified(),
			'verified_dealer' => $user->isVerifiedDealer()
		]);
	}

	/**
	 * Logout user
	 */
	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();
	}

	/**
	 * Logout all user's tokens
	 */
	public function logoutAll(Request $request)
	{
		$request->user()->tokens()->delete();
	}

	/**
	 * change password
	 */
	public function changePassword(Request $request)
	{
		$request->validate([
			'old_password' => 'required|string|min:8',
			'new_password' => 'required|string|min:8',
		]);

		$user = auth()->user();

		$user->update([
			'password' => Hash::make($request->password),
		]);

		return response()->json([
			'message' => 'Password reset successfully',
		], 200);
	}

	/**
	 * send reset password otp
	 */
	public function sendResetPasswordOtp(Request $request)
	{
		$request->validate([
			'phone' => 'required_without:email|string',
			'email' => 'required_without:phone|email',
		]);

		$user = User::where('phone', $request->phone)
			->orWhere('email', $request->email)
			->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'phone' => ['User not found.'],
			]);
		}

		$otp = Otp::generate($user->phone ?? $user->email);

		$smsDriver = new \App\Services\Sms\ArkeselSmsDriver();
		$smsDriver->send($user->phone ?? $user->email, "Your OTP is: {$otp}");

		// Send Email using Laravel's built-in mail
		// \Mail::raw("Your OTP is: {$otp}", function ($message) use ($user) {
		// 	$message->to($user->email)
		// 		->subject('Your OTP Code');
		// });

		$this->sendEmailWithSmtpSettings($user->email, $otp, $user->name);

		return response()->json([
			'message' => 'OTP sent successfully'
		], 200);
	}

	/**
	 * reset password
	 */
	public function resetPassword(Request $request)
	{
		$request->validate([
			'phone' => 'required_without:email|string',
			'email' => 'required_without:phone|email',
			'otp' => 'required|string|size:6',
			'password' => 'required|string|min:8',
		]);

		$user = User::where('phone', $request->phone)
			->orWhere('email', $request->email)
			->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'phone' => ['User not found.'],
			]);
		}

		$token = $user->createToken($request->phone)->plainTextToken;

		$user->update([
			'password' => Hash::make($request->password),
		]);

		return response()->json([
			'message' => 'Password reset successfully',
			'user' => $user,
			'token' => $token
		], 200);
	}

	/**
	 * update profile
	 */
	public function updateProfile(Request $request)
	{
		$user = $request->user();

		$request->validate([
			'name' => 'required|string|max:255',
			'email' => [
				'required',
				'string',
				'email',
				'max:255',
				Rule::unique('users')->ignore($user->id),
			],
			'phone' => [
				'nullable',
				'string',
				'max:255',
				Rule::unique('users')->ignore($user->id),
			],
			'password' => 'nullable|string|min:8',
			'device_name' => 'nullable',
			'profile_photo' => 'nullable|string',
			'country_id' => 'nullable|exists:countries,id',
			'state_id' => 'nullable|exists:states,id',
		]);

		$user->update([
			'name' => $request->name,
			'email' => $request->email,
			'phone' => $request->phone,
			'password' => $request->password ? Hash::make($request->password) : $user->password,
			'profile_photo' => $request->profile_photo,
			'country_id' => $request->country_id,
			'state_id' => $request->state_id,
		]);

		return response()->json([
			'message' => 'Profile updated successfully',
			'user' => $user->load('country', 'state')
		], 200);
	}

	/**
	 * Send email using SMTP settings from database
	 */
	private function sendEmailWithSmtpSettings(string $to, string $otp, string $userName = ''): void
	{
		// Check if SMTP is configured in database
		if (SmtpSettingsService::isSmtpConfigured()) {
			// Get SMTP configuration from database
			$smtpConfig = SmtpSettingsService::getSmtpConfig();

			// Temporarily update mail configuration
			Config::set('mail.mailers.smtp', $smtpConfig['mailers']['smtp']);
			Config::set('mail.from', $smtpConfig['from']);

			// Send email using Mailable
			\Mail::to($to)->send(new OtpMail($otp, $userName));
		} else {
			// Fallback to default mail configuration
			\Mail::to($to)->send(new OtpMail($otp, $userName));
		}
	}

	/**
	 * Delete account
	 */
	public function deleteAccount()
	{
		auth()->user()->delete();

		return response()->json([
			'message' => 'Account deleted successfully'
		], 200);
	}
}
