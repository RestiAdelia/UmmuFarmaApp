<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;

class BookingController extends Controller
{
    use ApiResponse;

    /**
     * List user's bookings.
     */
    public function index(Request $request)
    {
        // Auto-expire: Tandai booking yg jadwalnya sudah lewat hari ini
        Booking::where('user_id', $request->user()->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereHas('jadwal', function ($q) {
                $q->where('tgl_jadwal', '<', now()->toDateString());
            })
            ->update(['status' => 'expired']);

        $bookings = Booking::with([
            'jadwal',
            'jadwal.layanan',
            'ticket',
        ])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($bookings, 'Daftar booking berhasil diambil.');
    }

    /**
     * Store a new booking.
     */
    public function store(Request $request)
    {
        $request->validate([
            'jadwal_id'     => 'required|exists:jadwal,UniqueID',
            'nama_pasien'   => 'required|string|max:255',
            'no_hp'         => 'required|string|max:20',
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Cari & Lock jadwal berdasarkan UniqueID (UUID)
            $jadwal = Jadwal::where('UniqueID', $request->jadwal_id)->lockForUpdate()->first();

            if (!$jadwal) {
                return $this->error('Jadwal tidak ditemukan.', 404);
            }

            // 2. Cek apakah jam operasional di-OFF-kan oleh admin
            if (!$jadwal->is_aktif) {
                return $this->error('Maaf, jam operasional ini sedang dinonaktifkan (OFF).', 422);
            }

            // 3. Cek apakah gender pasien sesuai dengan target jadwal
            if ($jadwal->jk_target !== 'semua' && $jadwal->jk_target !== $request->jenis_kelamin) {
                return $this->error('Jadwal ini khusus untuk ' . $jadwal->jk_target, 422);
            }

            // 4. LOGIKA UTAMA: Cek kuota lintas layanan
            // Jika jam 08:00 Pria sudah dibooking di Layanan Pijat, 
            // maka Layanan Bekam di jam 08:00 Pria juga harus dianggap penuh.
            $exists = Booking::whereHas('jadwal', function ($q) use ($jadwal) {
                $q->where('tgl_jadwal', $jadwal->tgl_jadwal)
                    ->where('jam_mulai', $jadwal->jam_mulai);
            })
                ->where('jenis_kelamin', $request->jenis_kelamin)
                ->whereIn('status', ['confirmed', 'pending']) // Cek booking yang masih aktif
                ->exists();

            if ($exists) {
                return $this->error('Maaf, kuota untuk ' . $request->jenis_kelamin . ' di jam ini sudah terisi oleh layanan lain.', 422);
            }

            // 5. Cek kuota pada record jadwal itu sendiri (sebagai double check)
            if ($jadwal->jml_terjadwal >= $jadwal->kuota) {
                return $this->error('Slot ini sudah penuh.', 422);
            }

            // 6. Eksekusi Pembuatan Booking
            $booking = Booking::create([
                'user_id'       => $request->user()->id,
                'jadwal_id'     => $jadwal->UniqueID, // Simpan UUID
                'nama_pasien'   => $request->nama_pasien,
                'no_hp'         => $request->no_hp,
                'jenis_kelamin' => $request->jenis_kelamin,
                'status'        => 'confirmed',
                'booking_at'    => now(),
            ]);

            // 7. Update jumlah terjadwal
            $jadwal->increment('jml_terjadwal');

            // 8. Generate Tiket
            $code = Tiket::generateCode();
            Tiket::create([
                'booking_id'  => $booking->id,
                'code_ticket' => $code,
                'data_qr'     => $code,
            ]);

            // 9. Catat log status awal
            $booking->logStatus('pending', 'confirmed', $request->user()->id);

            return $this->success($booking->load(['jadwal.layanan', 'ticket']), 'Booking berhasil dilakukan.');
        });
    }

    /**
     * Store a new booking with pessimistic locking to prevent race conditions.
     */
    public function storeBooking(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Admin: Kelola Jam Operasional (On/Off)
     */
    public function toggleJadwal(Request $request)
    {
        $request->validate([
            'tgl_jadwal' => 'required|date',
            'jam_mulai'  => 'required',
            'is_aktif'   => 'required|boolean'
        ]);

        // Mengubah status ON/OFF jam tertentu di semua layanan sekaligus
        Jadwal::where('tgl_jadwal', $request->tgl_jadwal)
            ->where('jam_mulai', $request->jam_mulai)
            ->update(['is_aktif' => $request->is_aktif]);

        return $this->success(null, 'Status operasional jam tersebut berhasil diperbarui.');
    }

    /**
     * Show booking detail.
     */
    public function show(int $id)
    {
        $booking = Booking::with(['jadwal.layanan', 'ticket'])->findOrFail($id);
        return $this->success($booking, 'Detail booking berhasil diambil.');
    }

    /**
     * Scan check-in (Petugas).
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $tiket = Tiket::where('code_ticket', $request->qr_code)->first();

        if (!$tiket) {
            return $this->error('Tiket tidak valid.', 404);
        }

        if ($tiket->cek_in) {
            return $this->error('Tiket sudah digunakan.', 422);
        }

        // Validasi: check-in hanya bisa dilakukan di hari yang sama dengan jadwal
        $booking = $tiket->booking;
        if (!$booking) {
            return $this->error('Data booking tidak ditemukan.', 404);
        }

        $jadwal = $booking->jadwal;
        if ($jadwal) {
            $tglJadwal = \Carbon\Carbon::parse($jadwal->tgl_jadwal)->toDateString();
            $today = now()->toDateString();

            if ($tglJadwal !== $today) {
                $tglFormatted = \Carbon\Carbon::parse($tglJadwal)->translatedFormat('d F Y');
                if ($tglJadwal > $today) {
                    return $this->error("Check-in belum dapat dilakukan. Jadwal terapi adalah pada tanggal $tglFormatted.", 422);
                } else {
                    return $this->error("Tiket ini sudah melewati tanggal jadwal ($tglFormatted) dan tidak dapat digunakan.", 422);
                }
            }
        }

        return DB::transaction(function () use ($tiket, $request) {
            $tiket->update([
                'cek_in'  => true,
                'scan_at' => now(),
                'scan_by' => $request->user()->id,
            ]);

            $booking = $tiket->booking;
            $oldStatus = $booking->status;
            $booking->update(['status' => 'done']);
            $booking->logStatus($oldStatus, 'done', $request->user()->id);

            return $this->success($tiket->load('booking'), 'Check-in berhasil. Selamat menjalani terapi.');
        });
    }

    /**
     * Admin: Laporan Pemesanan
     */
    public function getLaporanPemesanan(Request $request)
    {
        $query = Booking::with([
            'user:id,name,email',
            'jadwal.layanan:id,nama_layanan,durasi',
            'ticket:id,booking_id,code_ticket,cek_in,scan_at'
        ])->orderByDesc('created_at');

        // Filter berdasarkan tanggal jika ada
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $bookings = $query->get();

        // Kalkulasi Statistik
        $summary = [
            'total' => $bookings->count(),
            'selesai' => $bookings->where('status', 'done')->count(),
            'menunggu' => $bookings->whereIn('status', ['pending', 'confirmed'])->count(),
            'batal' => $bookings->where('status', 'canceled')->count(),
        ];

        return $this->success([
            'summary' => $summary,
            'list' => $bookings
        ], 'Laporan pemesanan berhasil diambil.');
    }
    public function getBookingStatsToday(Request $request)
    {
        $today = now()->toDateString();
        
        // Menghitung booking yang masuk (dibuat) hari ini
        $total = Booking::whereDate('created_at', $today)->count();
        
        return $this->success([
            'total_booking_today' => $total
        ], 'Statistik booking hari ini berhasil diambil.');
    }
}
