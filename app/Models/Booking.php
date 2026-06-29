<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'user_id',
        'jadwal_id',
        'nama_pasien',
        'no_hp',
        'jenis_kelamin',
        'jk_cocok',
        'status',
        'booking_at',
        'confirmasi_at',
    ];
 
    protected $casts = [
        'jk_cocok'      => 'boolean',
        'booking_at'    => 'datetime',
        'confirmasi_at' => 'datetime',
    ];
 
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'UniqueID');
    }
    
 
   public function ticket()
    {
        // Sesuaikan dengan nama model Tiket kamu. 
        // Parameter ke-2 adalah foreign key di tabel tiket (misal: booking_id)
        return $this->hasOne(Tiket::class, 'booking_id', 'id'); 
    }
 
    public function logs()
    {
        return $this->hasMany(Bookinglogs::class);
    }
 
    //  Helper: catat perubahan status
    public function logStatus(string $statusFrom, string $statusTo, int $changedBy): void
    {
        $this->logs()->create([
            'status_from' => $statusFrom,
            'status_to'   => $statusTo,
            'changed_at'  => now(),
            'changed_by'  => $changedBy,
        ]);
    }
}
