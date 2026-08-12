<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'name'          => 'required|string|max:60',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'no_hp'         => 'nullable|string|max:20',
            'role'          => 'nullable|string|in:admin,petugas,pasien',
            'nama_lengkap'  => 'nullable|string|max:60',
            'jenis_kelamin' => 'nullable|string|in:laki-laki,perempuan',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'no_hp'    => $validated['no_hp'] ?? null,
                'role'     => $validated['role'] ?? 'pasien',
            ]);

            if ($user->role === 'pasien') {
                $user->pasien()->create([
                    'no_hp'         => $validated['no_hp'] ?? null,
                    'status'        => 'pending',
                    'nama_lengkap'  => $validated['nama_lengkap'] ?? $validated['name'],
                    'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                return $this->success(
                    ['user' => $user->load('pasien'), 'token' => $token],
                    'Pendaftaran berhasil. Silakan tunggu konfirmasi admin.',
                    201
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->success(
                ['user' => $user, 'token' => $token],
                'Registrasi berhasil.',
                201
            );
        });
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

        if ($user->role === 'pasien') {
            $pasien = $user->pasien;
            if (!$pasien || $pasien->status === 'pending') {
                return $this->error('Akun Anda sedang menunggu konfirmasi admin.', 403);
            }
            if ($pasien->status === 'ditolak') {
                return $this->error('Akun Anda ditolak oleh admin.', 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            ['user' => $user->load('pasien'), 'token' => $token],
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
        return $this->success($request->user()->load('pasien'), 'Data user berhasil diambil.');
    }

    /**
     * PUT /api/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:60',
            'no_hp'         => 'sometimes|nullable|string|max:20',
            'nama_lengkap'  => 'sometimes|nullable|string|max:60',
            'jenis_kelamin' => 'sometimes|nullable|string|in:laki-laki,perempuan',
        ]);

        $user->update([
            'name'  => $validated['name'] ?? $user->name,
            'no_hp' => $validated['no_hp'] ?? $user->no_hp,
        ]);

        if ($user->role === 'pasien') {
            $user->pasien()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap'  => $validated['nama_lengkap'] ?? ($validated['name'] ?? ($user->pasien->nama_lengkap ?? $user->name)),
                    'jenis_kelamin' => $validated['jenis_kelamin'] ?? ($user->pasien->jenis_kelamin ?? null),
                    'no_hp'         => $validated['no_hp'] ?? ($user->no_hp ?? ($user->pasien->no_hp ?? null)),
                ]
            );
        }

        return $this->success($user->load('pasien'), 'Profil berhasil diperbarui.');
    }

    /**
     * POST /api/profile/photo
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto_profil' => 'required|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
        ]);

        $user = $request->user();

        if ($request->hasFile('foto_profil')) {
            // Delete old photo if exists
            if ($user->foto_profil) {
                $oldPath = str_replace(url('storage') . '/', '', $user->foto_profil);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('foto_profil');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profiles', $filename, 'public');

            $user->update([
                'foto_profil' => url('storage/' . $path)
            ]);
        }

        return $this->success($user->load('pasien'), 'Foto profil berhasil diperbarui.');
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

    /**
     * Admin: List pending patient registrations.
     */
    public function listPendingPasien(Request $request)
    {
        $pendingPasiens = \App\Models\Pasien::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return $this->success($pendingPasiens, 'Daftar pendaftaran pasien berhasil diambil.');
    }

    /**
     * Admin: Confirm patient registration.
     */
    public function konfirmasiPasien(Request $request, int $id)
    {
        $pasien = \App\Models\Pasien::findOrFail($id);
        $pasien->update(['status' => 'aktif']);

        return $this->success($pasien->load('user'), 'Pendaftaran akun pasien berhasil dikonfirmasi.');
    }

    /**
     * Admin: Reject patient registration.
     */
    public function tolakPasien(Request $request, int $id)
    {
        $pasien = \App\Models\Pasien::findOrFail($id);
        $pasien->update(['status' => 'ditolak']);

        return $this->success($pasien->load('user'), 'Pendaftaran akun pasien berhasil ditolak.');
    }

    public function listAllPasien(Request $request)
    {
        $paginated = \App\Models\Pasien::with('user')
            ->where('status', 'aktif')
            ->latest()
            ->paginate(10);

        return $this->success([
            'data'         => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ], 'Daftar semua pasien berhasil diambil.');
    }
}
