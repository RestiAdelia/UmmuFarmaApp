<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LayananTerapi;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\Support\Str;

class JadwalSeeder extends Seeder
{
    public function run(): void
    {
        $layananList = [
            [
                'id' => 1,
                'nama_layanan' => 'Pijat Tradisional',
                'status' => 'aktif',
                'deskripsi' => 'Terapi pijat tradisional untuk kebugaran tubuh.',
                'durasi' => 60,
            ],
            [
                'id' => 2,
                'nama_layanan' => 'Terapi Bekam (Hijama)',
                'status' => 'aktif',
                'deskripsi' => 'Terapi pengeluaran darah kotor sesuai sunnah.',
                'durasi' => 60,
            ],
            [
                'id' => 3,
                'nama_layanan' => 'Akupunktur',
                'status' => 'aktif',
                'deskripsi' => 'Terapi penusukan jarum halus pada titik meridian.',
                'durasi' => 60,
            ],
        ];

        foreach ($layananList as $lay) {
            LayananTerapi::updateOrCreate(['id' => $lay['id']], $lay);
        }

        $layanans = LayananTerapi::where('status', 'aktif')->get();
        $startDate = Carbon::today();

        // Generate jadwal dari hari ini hingga 60 hari ke depan
        // Jalankan seeder ini secara berkala agar jadwal selalu tersedia
        for ($i = 0; $i < 60; $i++) {
            $targetDate = $startDate->copy()->addDays($i);

            // Jam operasional 08:00 sampai 22:00 (slot terakhir mulai jam 21:00)
            for ($hour = 8; $hour < 22; $hour++) {
                $jamMulai = sprintf('%02d:00:00', $hour);
                $jamSelesai = sprintf('%02d:00:00', $hour + 1);

                foreach ($layanans as $layanan) {
                    $genders = ['laki-laki', 'perempuan'];

                    foreach ($genders as $gender) {
                        // firstOrCreate: hanya buat baru jika belum ada.
                        // Tidak menimpa UniqueID yang sudah ada (agar booking lama tidak rusak).
                        Jadwal::firstOrCreate(
                            [
                                'layanan_id' => $layanan->id,
                                'tgl_jadwal' => $targetDate->format('Y-m-d'),
                                'jam_mulai'  => $jamMulai,
                                'jk_target'  => $gender,
                            ],
                            [
                                'UniqueID'        => (string) Str::uuid(),
                                'jam_berakhir'    => $jamSelesai,
                                'kuota'           => 1,
                                'jml_terjadwal'   => 0,
                                'is_aktif'        => true,
                                'jadwal_terkunci' => false,
                            ]
                        );
                    }
                }
            }
        }
    }
}