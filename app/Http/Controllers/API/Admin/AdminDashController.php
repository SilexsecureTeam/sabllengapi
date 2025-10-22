<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminDashController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        // Check user existence & password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // ✅ Restrict to admin and superadmin only

        if ($user->role !== 'admin' && $user->role !== 'superadmin') {
            return response()->json(['message' => 'Access denied. Admins only.'], 403);
        }

        // Generate and store OTP
        $otp = rand(1000, 9999);

        $user->update([
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP to email
        Mail::raw("Your Sablé admin verification code is: {$otp}", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Sablé Admin Login OTP');
        });

        return response()->json([
            'message' => 'OTP sent to your registered email address.',
            'data' => $user
        ]);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:4'
        ]);

        $user = User::where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        // ✅ Check if OTP is expired
        if (now()->greaterThan($user->expires_at)) {
            return response()->json(['message' => 'OTP expired'], 401);
        }

        // ✅ Clear OTP after successful verification
        $user->update([
            'otp' => null,
            'expires_at' => null,
        ]);

        // ✅ Generate Sanctum token
        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully. Logged in as admin.',
            'token' => $token,
            'user' => $user
        ]);
    }

    // Step 3: Resend OTP
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'No user found with this email'], 404);
        }

        // Restrict resend to admins only
        if ($user->role !== 'admin' && $user->role !== 'superadmin') {
            return response()->json(['message' => 'Access denied. Admins only.'], 403);
        }

        // Optional: Cooldown - prevent frequent resends (60s)
        $cacheKey = 'otp_resend_cooldown_' . $user->id;
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $secondsLeft = \Illuminate\Support\Facades\Cache::get($cacheKey) - time();
            if ($secondsLeft > 0) {
                return response()->json([
                    'message' => "Please wait {$secondsLeft} seconds before requesting a new OTP."
                ], 429);
            }
        }

        // Generate new OTP and expiry
        $otp = rand(1000, 9999);
        $user->update([
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Set cooldown
        \Illuminate\Support\Facades\Cache::put($cacheKey, time() + 60, 60);

        // Send OTP via email
        \Illuminate\Support\Facades\Mail::raw("Your Sablé admin verification code is: {$otp}", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Sablé Admin Login OTP');
        });

        return response()->json([
            'message' => 'OTP resent to your registered email address.',
        ]);
    }

    // list of orders

    public function listOrders(Request $request)
    {
        // Optional filters: status, payment_status, user_id
        $query = Order::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // if ($request->filled('date_from')) {
        //     $query->whereDate('created_at', '>=', $request->date_from);
        // }

        // if ($request->filled('date_to')) {
        //     $query->whereDate('created_at', '<=', $request->date_to);
        // }

        // Paginate results (default 15 per page)
        $orders = $query->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => $orders,
        ]);
    }
}
