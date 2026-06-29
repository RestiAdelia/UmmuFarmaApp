<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tiket extends Model
{
    use HasFactory;
 
    protected $table = 'table_tickets';

    protected $fillable = [
        'booking_id',
        'code_ticket',
        'data_qr',
        'cek_in',
        'scan_at',
        'scan_by',
    ];
 
    protected $casts = [
        'cek_in'  => 'boolean',
        'scan_at' => 'datetime',
    ];
 
    //  Generate kode tiket unik 
    public static function generateCode(): string
    {
        return strtoupper('TKT-' . Str::random(8));
    }
 
    // 
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
 
    public function scanner()
    {
        return $this->belongsTo(User::class, 'scan_by');
    }
}
