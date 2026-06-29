<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'no_hp'    => 'nullable|string|max:20',
            'role'     => 'nullable|string|in:admin,petugas,pasien',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_hp'    => $validated['no_hp'] ?? null,
            'role'     => $validated['role'] ?? 'pasien',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            ['user' => $user, 'token' => $token],
            'Registrasi berhasil.',
            201
        );
    }

    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah.', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            ['user' => $user, 'token' => $token],
            'Login berhasil.'
        );
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    /**
     * GET /api/me
     */
    public function me(Request $request)
    {
        return $this->success($request->user(), 'Data user berhasil diambil.');
    }

    /**
     * PUT /api/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'no_hp' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return $this->success($user, 'Profil berhasil diperbarui.');
    }

    /**
     * PUT /api/update-password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->error('Password lama tidak sesuai.', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Password berhasil diperbarui.');
    }
}
