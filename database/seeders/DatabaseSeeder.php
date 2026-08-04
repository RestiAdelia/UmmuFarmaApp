<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\Tiket;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat User utama dan Jadwal
        $this->call([
            UserSeeder::class,
            JadwalSeeder::class,
        ]);

        $this->command->info('Membuat 20 Pasien dummy (beserta akun User)...');
        
        // 2. Buat 20 Pasien Dummy
        User::factory(20)->create()->each(function ($user) {
            Pasien::factory()->create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'no_hp' => $user->no_hp,
            ]);
        });

        $this->command->info('Membuat 30 Booking dummy...');
        
        // 3. Buat 30 Booking Dummy secara acak
        $pasiens = Pasien::all();
        $jadwals = Jadwal::where('is_aktif', true)->get();
        
        if($pasiens->isNotEmpty() && $jadwals->isNotEmpty()) {
            for ($i = 0; $i < 30; $i++) {
                $pasien = $pasiens->random();
                $jadwal = $jadwals->random();
                
                $booking = Booking::factory()->create([
                    'user_id' => $pasien->user_id,
                    'jadwal_id' => $jadwal->UniqueID,
                    'nama_pasien' => $pasien->nama_lengkap,
                    'jenis_kelamin' => $pasien->jenis_kelamin,
                    'no_hp' => $pasien->no_hp,
                ]);

                // Update jumlah terjadwal di slot
                if (in_array($booking->status, ['pending', 'confirmed'])) {
                    $jadwal->increment('jml_terjadwal');
                }

                // Buatkan tiket otomatis jika sudah dikonfirmasi
                if (in_array($booking->status, ['confirmed', 'done'])) {
                    Tiket::factory()->create([
                        'booking_id' => $booking->id,
                        'cek_in' => $booking->status === 'done',
                    ]);
                }
            }
        }
        
        $this->command->info('✅ Seluruh dummy data berhasil di-generate!');
    }
}
