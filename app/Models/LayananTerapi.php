<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayananTerapi extends Model
{
    protected $table = 'layanan_terapis';

    protected $fillable = [
        'nama_layanan',
        'status',
        'deskripsi',
        'tarif',
        'durasi',
        'gambar'
    ];

    /**
     * Relasi ke Jadwal
     */
    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'layanan_id');
    }
}
