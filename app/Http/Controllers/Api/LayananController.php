<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LayananTerapi;
use App\Http\Requests\Layanan\StoreLayananRequest;
use App\Http\Requests\Layanan\UpdateLayananRequest;
use App\Http\Resources\LayananResource;
use App\Helpers\ApiResponse;

class LayananController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LayananTerapi::query();

        if (!$request->user() || $request->user()->isPasien()) {
            $query->where('status', 'aktif');
        }

        $layanan = $query->latest()->get();

        return $this->success(
            LayananResource::collection($layanan),
            'Daftar layanan berhasil diambil.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $layanan = LayananTerapi::findOrFail($id);
        
        return $this->success(
            new LayananResource($layanan),
            'Detail layanan berhasil diambil.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLayananRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('layanan', 'public');
        }

        $layanan = LayananTerapi::create($data);
        
        return $this->success(
            new LayananResource($layanan),
            'Layanan berhasil ditambahkan.',
            201
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLayananRequest $request, int $id)
    {
        $layanan = LayananTerapi::findOrFail($id);
        $data = $request->validated();
        if ($request->hasFile('gambar')) {
            if ($layanan->gambar && \Storage::disk('public')->exists($layanan->gambar)) {
                \Storage::disk('public')->delete($layanan->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('layanan', 'public');
        }

        $layanan->update($data);
        
        return $this->success(
            new LayananResource($layanan),
            'Layanan berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $layanan = LayananTerapi::findOrFail($id);

        if ($layanan->jadwal()->exists()) {
            return $this->error(
                'Layanan tidak bisa dihapus karena masih memiliki jadwal.',
                422
            );
        }

        $layanan->delete();
        
        return $this->success(
            null,
            'Layanan berhasil dihapus.'
        );
    }
}
