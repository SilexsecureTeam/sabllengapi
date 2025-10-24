<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\PasswordResetToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // ✅ Let Laravel handle validation normally
        $request->validate([
            'name' => 'required|string',
            'username' => 'nullable|string|unique:users,username',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // generate 4-digit code
            $code = rand(1000, 9999);

            VerificationCode::create([
                'user_id'    => $user->id,
                'code'       => $code,
                'expires_at' => Carbon::now()->addMinutes(45),
            ]);

            // try sending mail (but don’t break if it fails)
            try {
                Mail::raw("Your verification code is: $code", function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Verify Your Account');
                });
            } catch (\Exception $e) {
                // \Log::error('Mail sending failed: ' . $e->getMessage());
            }

            $response = [
                'message' => 'User registered. Verification code sent to email and phone.'
            ];

            if (app()->environment(['local', 'development'])) {
                $response['code'] = $code;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            // \Log::error('Registration process failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Registration failed',
                'error'   => app()->environment('local') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    // verification method
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $verification = VerificationCode::where('code', $request->code)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $user = $verification->user;

        // mark user as verified
        $user->email_verified_at = now();
        $user->save();

        // delete used code
        $verification->delete();

        // issue login token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',  // username or email
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        // find user by email OR username
        $user = User::where('email', $request->login)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Please verify your email before login.'], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // create token with "remember me" expiration
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        // default: 2 hours, remember_me: 30 days
        $expiry = $request->remember_me
            ? now()->addDays(30)
            : now()->addHours(2);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiry,
        ]);
    }

    public function sendResetLink(Request $request)
    {

        // Validate email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Attempt to send reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status)
            ], 200);
        }

        return response()->json([
            'message' => __($status)
        ], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Find token record
        $reset = PasswordResetToken::where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        // Check expiry time
        if (Carbon::now()->greaterThan($reset->expires_at)) {
            return response()->json(['message' => 'Token has expired.'], 400);
        }

        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete used token
        $reset->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now log in.',
        ]);
    }
}
