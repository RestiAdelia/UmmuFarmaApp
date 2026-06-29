<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
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

        $jadwal = Jadwal::where('layanan_id', $request->layanan_id)
            ->where('tgl_jadwal', $request->tanggal)
            ->where('is_aktif', true)
            ->where('jadwal_terkunci', false)
            ->whereColumn('jml_terjadwal', '<', 'kuota')
            ->where(function($query) use ($request) {
                $query->where('jk_target', $request->gender)
                      ->orWhere('jk_target', 'semua');
            })
            ->whereNotExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->join('jadwal as j2', 'bookings.jadwal_id', '=', 'j2.UniqueID')
                    ->whereColumn('j2.tgl_jadwal', 'jadwal.tgl_jadwal')
                    ->whereColumn('j2.jam_mulai', 'jadwal.jam_mulai')
                    ->where('bookings.jenis_kelamin', $request->gender)
                    ->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->get();

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

        $jadwal = Jadwal::where('layanan_id', $request->layanan_id)
            ->where('tgl_jadwal', $request->tanggal)
            ->where('is_aktif', true)
            ->where('jadwal_terkunci', false)
            ->whereColumn('jml_terjadwal', '<', 'kuota')
            ->where(function($query) use ($request) {
                $query->where('jk_target', $request->gender)
                      ->orWhere('jk_target', 'semua');
            })
            ->whereNotExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->join('jadwal as j2', 'bookings.jadwal_id', '=', 'j2.UniqueID')
                    ->whereColumn('j2.tgl_jadwal', 'jadwal.tgl_jadwal')
                    ->whereColumn('j2.jam_mulai', 'jadwal.jam_mulai')
                    ->where('bookings.jenis_kelamin', $request->gender)
                    ->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->get();

        if ($jadwal->isEmpty()) {
            return $this->error('Jadwal tidak tersedia untuk kriteria ini.', 404);
        }

        return $this->success($jadwal, 'Daftar jadwal tersedia berhasil diambil.');
    }

    /**
     * Admin: Kelola Jam Operasional (On/Off)
     */
    public function toggleOperasional(Request $request)
    {
        $request->validate([
            'tgl_jadwal' => 'required|date',
            'jam_mulai'  => 'required',
            'is_aktif'   => 'required|boolean'
        ]);

        // Matikan jam tersebut di SEMUA layanan
        Jadwal::where('tgl_jadwal', $request->tgl_jadwal)
            ->where('jam_mulai', $request->jam_mulai)
            ->update(['is_aktif' => $request->is_aktif]);

        return $this->success(null, 'Status operasional jam tersebut berhasil diperbarui.');
    }
}
