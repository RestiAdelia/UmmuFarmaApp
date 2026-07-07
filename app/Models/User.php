<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;
   
 
    protected $fillable = [
        'name',
        'email',
        'password',
        'no_hp',
        'role',
    ];
 
    protected $hidden = [
        'password',
        'remember_token',
    ];
 
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
 
    // ── Helpers role ─────────────────────────────────────
    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isPetugas(): bool  { return $this->role === 'petugas'; }
    public function isPasien(): bool   { return $this->role === 'pasien'; }
 
    // ── Relasi ───────────────────────────────────────────
    public function pasien()
    {
        return $this->hasOne(Pasien::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
 
    public function bookingLogs()
    {
        return $this->hasMany(BookingLogs::class, 'changed_by');
    }
 
    public function ticketsScanned()
    {
        return $this->hasMany(Tiket::class, 'scan_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            ''
        ];
    }
}
