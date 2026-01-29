<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;



class GoogleAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'id_token' => 'required|string',
            ]);

            $idToken = $request->input('id_token');

            // Verify token
            $client = new GoogleClient([
                'client_id' => config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID')
            ]);

            $payload = $client->verifyIdToken($idToken);
            if (!$payload) {
                throw ValidationException::withMessages(['id_token' => ['Invalid Google ID token.']]);
            }

            // Extract payload data
            $googleId = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;
            $email_verified = $payload['email_verified'] ?? false;

            if (!$googleId || !$email) {
                throw ValidationException::withMessages(['id_token' => ['Google token missing required fields.']]);
            }

            // Find existing user by google_id or email
            $user = User::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                // Create new user for unregistered customers
                $user = User::create([
                    'name' => $name ?? explode('@', $email)[0],
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $picture,
                    'password' => bcrypt(Str::random(32)), // Random password
                    'email_verified_at' => $email_verified ? now() : null,
                ]);
            } else {
                // Update existing user
                if (!$user->google_id) {
                    $user->google_id = $googleId;
                }
                if (!$user->avatar && $picture) {
                    $user->avatar = $picture;
                }
                if (!$user->email_verified_at && $email_verified) {
                    $user->email_verified_at = now();
                }
                $user->save();
            }

            // Create Sanctum token
            $token = $user->createToken('google-auth-' . Str::random(10))->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => $user->only(['id', 'name', 'email', 'avatar']),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
