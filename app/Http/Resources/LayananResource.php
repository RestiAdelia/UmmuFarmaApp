<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayananResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gambarUrl = null;
        if ($this->gambar) {
            if (filter_var($this->gambar, FILTER_VALIDATE_URL)) {
                $gambarUrl = $this->gambar;
            } else {
                $gambarUrl = asset('storage/' . $this->gambar);
            }
        }

        return [
            'id' => $this->id,
            'nama_layanan' => $this->nama_layanan,
            'gambar' => $gambarUrl,
            'status' => $this->status,
            'deskripsi' => $this->deskripsi,
            'durasi' => (int) $this->durasi,
            'tarif' => (float) $this->tarif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
