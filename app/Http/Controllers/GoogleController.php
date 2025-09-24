<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google OAuth not configured properly: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                    'profile_photo' => $googleUser->getAvatar()
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            // Samakan dengan konfigurasi di .env
            return redirect('http://localhost:5173/auth/callback?token=' . $token . '&user=' . base64_encode(json_encode($user)));
        } catch (\Exception $e) {
            return redirect('http://localhost:5173/login?error=google_auth_failed');
        }
    }
}
