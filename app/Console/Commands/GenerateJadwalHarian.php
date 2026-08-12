<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JadwalGeneratorService;

class GenerateJadwalHarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jadwal:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate jadwal mingguan (otomatis 7 hari ke depan) untuk semua layanan yang aktif';
    /**
     * Execute the console command.
     */
    public function handle(JadwalGeneratorService $generatorService)
    {
        $this->info('Memulai pembuatan jadwal harian...');

        $jumlahDibuat = $generatorService->generateUntukSemuaLayananAktif();

        $this->info("Proses selesai. Sebanyak {$jumlahDibuat} slot jadwal baru telah dibuat.");
    }
}
