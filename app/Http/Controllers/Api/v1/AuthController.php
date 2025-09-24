<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        Log::info('Register attempt:', $request->all());

        // Line 10-13 sudah benar
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // ✅ ini benar
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully:', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        Log::info('Login attempt:', ['email' => $request->email]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Failed login attempt:', ['email' => $request->email]);

            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Revoke all existing tokens untuk user ini (optional - untuk single session)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('User logged in successfully:', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Delete current access token
        $request->user()->currentAccessToken()->delete();

        Log::info('User logged out:', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();

        // Delete all tokens for this user
        $user->tokens()->delete();

        Log::info('User logged out from all devices:', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out from all devices successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8|confirmed',
            'current_password' => 'required_with:password', // Require current password when updating password
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If updating password, verify current password
        if ($request->has('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password saat ini tidak benar'
                ], 400);
            }
        }

        try {
            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            Log::info('Profile updated:', ['user_id' => $user->id, 'fields' => array_keys($updateData)]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $user->fresh() // Get fresh data from database
            ]);
        } catch (\Exception $e) {
            Log::error('Profile update failed:', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Profile update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->hasFile('photo')) {
                // hapus foto lama kalau ada
                if ($user->profile_photo && file_exists(public_path($user->profile_photo))) {
                    unlink(public_path($user->profile_photo));
                }

                $photo = $request->file('photo');
                $filename = 'profile_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();

                // simpan langsung ke public/profiles
                $photo->move(public_path('profiles'), $filename);

                // simpan path relatif (tanpa "storage/")
                $user->update(['profile_photo' => 'profiles/' . $filename]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Photo updated successfully',
                    'user' => $user->fresh(),
                    'photo_url' => url('profiles/' . $filename)
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No photo uploaded'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Photo update failed: ' . $e->getMessage()
            ], 500);
        }
    }


    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password saat ini tidak benar'
            ], 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Optional: Logout from all other devices
            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            Log::info('Password changed:', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Password change failed:', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Password change failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
