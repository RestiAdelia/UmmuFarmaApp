<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LayananTerapi;
use App\Services\JadwalGeneratorService;

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

        // Generate jadwal untuk semua layanan yang baru saja di-seed
        $generator = app(JadwalGeneratorService::class);
        $totalSlot = $generator->generateUntukSemuaLayananAktif();
        
        $this->command->info("JadwalSeeder selesai. Sebanyak {$totalSlot} slot jadwal baru dibuat menggunakan JadwalGeneratorService.");
    }
}