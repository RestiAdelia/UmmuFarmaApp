<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JadwalController extends Controller
{
    use ApiResponse;

    /**
     * List available schedules for a service and date.
     */
    public function index(Request $request)
    {
        $request->validate([
            'layanan_id' => 'required|exists:layanan_terapis,id',
            'tanggal'    => 'required|date|after_or_equal:today',
            'gender'     => 'required|in:laki-laki,perempuan',
        ]);

        $query = Jadwal::where('layanan_id', $request->layanan_id)
            ->where('tgl_jadwal', $request->tanggal)
            ->where('is_aktif', true)
            ->where('jadwal_terkunci', false)
            ->whereColumn('jml_terjadwal', '<', 'kuota')
            ->where(function($q) use ($request) {
                $q->where('jk_target', $request->gender)
                  ->orWhere('jk_target', 'semua');
            })
            ->whereNotExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('bookings')
                    ->join('jadwal as j2', 'bookings.jadwal_id', '=', 'j2.UniqueID')
                    ->whereColumn('j2.tgl_jadwal', 'jadwal.tgl_jadwal')
                    ->whereColumn('j2.jam_mulai', 'jadwal.jam_mulai')
                    ->where('bookings.jenis_kelamin', $request->gender)
                    ->whereIn('bookings.status', ['confirmed', 'pending']);
            });

        if ($request->tanggal === \Carbon\Carbon::today()->format('Y-m-d')) {
            $currentTime = \Carbon\Carbon::now()->format('H:i:s');
            $query->where('jam_mulai', '>', $currentTime);
        }

        $jadwal = $query->get();

        return $this->success($jadwal, 'Daftar jadwal tersedia berhasil diambil.');
    }

    /**
     * Get available schedules strictly filtered by service, date, and gender (gender-segregated).
     */
    public function getAvailableSchedules(Request $request)
    {
        $request->validate([
            'layanan_id' => 'required|exists:layanan_terapis,id',
            'tanggal'    => 'required|date',
            'gender'     => 'required|in:laki-laki,perempuan',
        ]);

        $query = Jadwal::where('layanan_id', $request->layanan_id)
            ->where('tgl_jadwal', $request->tanggal)
            ->where('is_aktif', true)
            ->where('jadwal_terkunci', false)
            ->whereColumn('jml_terjadwal', '<', 'kuota')
            ->where(function($q) use ($request) {
                $q->where('jk_target', $request->gender)
                  ->orWhere('jk_target', 'semua');
            });

        if ($request->tanggal === \Carbon\Carbon::today()->format('Y-m-d')) {
            $currentTime = \Carbon\Carbon::now()->format('H:i:s');
            $query->where('jam_mulai', '>', $currentTime);
        }

        $jadwal = $query->orderBy('jam_mulai')->get();

        return $this->success($jadwal, 'Daftar jadwal tersedia berhasil diambil.');
    }

    /**
     * Admin: Kelola Jam Operasional (On/Off)
     *
     * PERUBAHAN: jam operasional sekarang bersifat GLOBAL, berlaku untuk
     * SEMUA layanan pada jam & tanggal yang sama (tidak lagi per layanan_id).
     * Slot yang sudah memiliki booking (jml_terjadwal > 0) tetap dilindungi
     * dan tidak akan ikut berubah statusnya.
     */
    public function toggleOperasional(Request $request)
    {
        // 1. Tentukan batas tanggal (Hari ini sampai 7 hari ke depan)
        $today = Carbon::today()->format('Y-m-d');
        $maxDate = Carbon::today()->addDays(7)->format('Y-m-d');

        // 2. Validasi input yang ketat
        $request->validate([
            'tgl_jadwal' => [
                'required',
                'date',
                "after_or_equal:$today",   // Tidak boleh tanggal kemarin
                "before_or_equal:$maxDate" // Maksimal seminggu ke depan
            ],
            'jam_mulai' => 'required|date_format:H:i:s',
            'is_aktif'  => 'required|boolean'
        ], [
            // Custom pesan error agar user/admin paham batasannya
            'tgl_jadwal.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'tgl_jadwal.before_or_equal' => 'Jadwal hanya bisa diatur untuk maksimal 7 hari ke depan.',
        ]);

        // 3. Eksekusi Update LINTAS SEMUA LAYANAN (tanpa filter layanan_id)
        // Tetap hanya mengupdate jadwal yang belum ada booking sama sekali
        // (jml_terjadwal = 0), agar slot yang sudah dipesan pasien di layanan
        // manapun tidak ikut berubah statusnya.
        $updatedCount = Jadwal::where('tgl_jadwal', $request->tgl_jadwal)
            ->where('jam_mulai', $request->jam_mulai)
            ->where('jml_terjadwal', 0)
            ->update(['is_aktif' => $request->is_aktif]);

        // 4. Respon yang informatif
        if ($updatedCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada slot tersedia yang bisa diubah pada jam tersebut (mungkin sudah dipesan atau jadwal tidak ada).'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status operasional berhasil diperbarui untuk semua layanan.',
            'data' => [
                'tanggal' => $request->tgl_jadwal,
                'jam' => $request->jam_mulai,
                'status_aktif' => $request->is_aktif,
                'total_layanan_terpengaruh' => $updatedCount
            ]
        ]);
    }

    /**
     * Admin: Daftar jam operasional GLOBAL untuk satu tanggal.
     *
     * Mengelompokkan jadwal per jam_mulai (lintas SEMUA layanan), supaya
     * admin bisa toggle satu jam dan itu berlaku ke seluruh layanan.
     * - is_aktif yang ditampilkan: true hanya jika SEMUA layanan pada jam
     *   itu aktif (kalau ada satu yang nonaktif, dianggap "campuran"/false,
     *   supaya tombol toggle konsisten dengan apa yang akan terjadi saat ditekan).
     * - kuota & jml_terjadwal dijumlahkan dari seluruh layanan pada jam itu,
     *   sekadar info kepadatan agar admin tahu ada booking aktif atau tidak.
     */
    public function getJadwalOperasional(Request $request)
    {
        $request->validate([
            'tgl_jadwal' => 'required|date',
        ]);

        $rows = Jadwal::where('tgl_jadwal', $request->tgl_jadwal)
            ->orderBy('jam_mulai')
            ->get();

        $grouped = $rows->groupBy('jam_mulai')->map(function ($items, $jamMulai) {
            return [
                'jam_mulai'      => $jamMulai,
                'jam_selesai'    => $items->first()->jam_selesai,
                'is_aktif'       => $items->every(fn ($i) => (bool) $i->is_aktif),
                'kuota'          => $items->sum('kuota'),
                'jml_terjadwal'  => $items->sum('jml_terjadwal'),
                'jumlah_layanan' => $items->count(),
            ];
        })->values();

        return $this->success($grouped, 'Daftar jam operasional berhasil diambil.');
    }

    /**
     * Get weekly schedules (from today to 7 days in the future).
     */
    public function getJadwalMingguan(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $maxDate = Carbon::today()->addDays(7)->format('Y-m-d');

        $request->validate([
            'layanan_id' => 'sometimes|exists:layanan_terapis,id',
        ]);

        $query = Jadwal::with('layanan')
            ->whereBetween('tgl_jadwal', [$today, $maxDate])
            ->orderBy('tgl_jadwal')
            ->orderBy('jam_mulai');

        if ($request->has('layanan_id')) {
            $query->where('layanan_id', $request->layanan_id);
        }

        $jadwal = $query->get();

        return $this->success($jadwal, 'Jadwal mingguan berhasil diambil.');
    }
}