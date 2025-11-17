<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;



class GoogleAuthController extends Controller
{
    public function login(Request $request)
    {
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

        // Payload contains fields like 'sub' (google user id), 'email', 'name', 'picture'
        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        $picture = $payload['picture'] ?? null;
        $email_verified = $payload['email_verified'] ?? false;

        if (!$googleId || !$email) {
            throw ValidationException::withMessages(['id_token' => ['Google token missing required fields.']]);
        }

        // Find or create the user
        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name ?? $email,
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $picture,
                // password null since Google login
            ]);
        } else {
            // Update google_id if missing (user registered via email before)
            if (!$user->google_id) {
                $user->google_id = $googleId;
                $user->avatar = $picture ?? $user->avatar;
                $user->save();
            }
        }

        // Create Sanctum token (personal access token)
        $token = $user->createToken('sanctum-token-' . Str::random(10))->plainTextToken;

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'avatar']),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke current token
        $user = $request->user();
        if ($user) {
            // Revoke the token used in this request
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
