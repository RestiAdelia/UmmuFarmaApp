<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ApiResponse;

class PetugasController extends Controller
{
    use ApiResponse;

    /**
     * List all petugas accounts.
     */
    public function index()
    {
        $petugas = User::where('role', 'petugas')->latest()->get();
        
        return $this->success($petugas, 'Daftar petugas berhasil diambil.');
    }

    /**
     * Store a new petugas account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'no_hp'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_hp'    => $validated['no_hp'] ?? null,
            'role'     => 'petugas',
        ]);

        return $this->success($user, 'Akun petugas berhasil ditambahkan.', 201);
    }

    /**
     * Update a petugas account.
     */
    public function update(Request $request, int $id)
    {
        $user = User::where('role', 'petugas')->findOrFail($id);
        
        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'no_hp'    => 'sometimes|nullable|string|max:20',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->success($user, 'Akun petugas berhasil diperbarui.');
    }

    /**
     * Remove a petugas account.
     */
    public function destroy(int $id)
    {
        $user = User::where('role', 'petugas')->findOrFail($id);
        
        // Prevent deleting if they have scanned tickets or something? 
        // For now just delete.
        
        $user->delete();

        return $this->success(null, 'Akun petugas berhasil dihapus.');
    }
}
