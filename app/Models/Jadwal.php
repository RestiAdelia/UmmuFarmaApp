<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwal';
    protected $primaryKey = 'UniqueID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'UniqueID',
        'layanan_id',
        'jk_target',
        'tgl_jadwal',
        'jam_mulai',
        'jam_berakhir',
        'kuota',
        'jml_terjadwal',
        'is_aktif',
        'jadwal_terkunci',
    ];

    protected $casts = [
        'tgl_jadwal'      => 'date',
        'is_aktif'        => 'boolean',
        'jadwal_terkunci' => 'boolean',
    ];

    /**
     * Auto-generate UUID untuk UniqueID saat model dibuat.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->UniqueID)) {
                $model->UniqueID = (string) Str::uuid();
            }
        });
    }

    // cek slot masih tersedia
    public function isTersedia(): bool
    {
        return !$this->jadwal_terkunci
            && $this->jml_terjadwal < $this->kuota;
    }

    public function layanan()
    {
        return $this->belongsTo(LayananTerapi::class, 'layanan_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'jadwal_id', 'UniqueID');
    }
}

