<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookinglogs extends Model
{
     protected $table = 'booking_logs';
 
    protected $fillable = [
        'booking_id',
        'status_from',
        'status_to',
        'changed_at',
        'changed_by',
    ];
 
    protected $casts = [
        'changed_at' => 'datetime',
    ];
 
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
 
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
