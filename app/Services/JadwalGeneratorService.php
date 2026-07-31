<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\LayananTerapi;
use Carbon\Carbon;
use Illuminate\Support\Str;

class JadwalGeneratorService
{
    /**
     * Generate slot jadwal untuk 1 layanan dari startDate sampai N hari ke depan.
     * Skip hari Jumat. Jam operasional 08:00 - 22:00.
     *
     * @param LayananTerapi $layanan
     * @param Carbon|null $startDate
     * @param int|null $jumlahHari
     * @return int Jumlah slot baru yang berhasil dibuat.
     */
    public function generateUntukLayanan(LayananTerapi $layanan, ?Carbon $startDate = null, ?int $jumlahHari = null): int
    {
        $startDate = $startDate ?? Carbon::today();
        $jumlahHari = $jumlahHari ?? 7;

        $genders = ['laki-laki', 'perempuan'];
        $createdCount = 0;

        for ($i = 0; $i < $jumlahHari; $i++) {
            $targetDate = $startDate->copy()->addDays($i);

            // jumat libur)
            if ($targetDate->isFriday()) {
                continue;
            }
            for ($hour = 9; $hour < 22; $hour++) {
                if ($hour === 12 || $hour === 18) {
                    continue;
                }

                $jamMulai = sprintf('%02d:00:00', $hour);
                $jamSelesai = sprintf('%02d:00:00', $hour + 1);

                foreach ($genders as $gender) {
                    $jadwal = Jadwal::firstOrCreate(
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

                    if ($jadwal->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }
        }

        return $createdCount;
    }

    /**
     * Loop semua LayananTerapi berstatus 'aktif' dan generate jadwal untuk masing-masing.
     *
     * @return int Total slot baru yang berhasil dibuat untuk seluruh layanan.
     */
    public function generateUntukSemuaLayananAktif(): int
    {
        $layanans = LayananTerapi::where('status', 'aktif')->get();
        $totalCreated = 0;

        foreach ($layanans as $layanan) {
            $totalCreated += $this->generateUntukLayanan($layanan);
        }

        return $totalCreated;
    }

    /**
     * Nonaktifkan slot kosong (belum ada booking) milik layanan terkait.
     * Digunakan saat layanan dinonaktifkan.
     *
     * @param LayananTerapi $layanan
     * @return int Jumlah slot yang dinonaktifkan.
     */
    public function nonaktifkanSlotKosong(LayananTerapi $layanan): int
    {
        return Jadwal::where('layanan_id', $layanan->id)
            ->where('jml_terjadwal', 0)
            ->update(['is_aktif' => false]);
    }
}
