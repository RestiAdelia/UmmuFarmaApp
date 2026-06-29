<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    // JadwalController.php (Admin)
    public function toggleOperasional(Request $request)
    {
        $request->validate([
            'tgl_jadwal' => 'required|date',
            'jam_mulai'  => 'required', // Contoh: 08:00:00
            'is_aktif'   => 'required|boolean'
        ]);

        // Matikan jam tersebut di SEMUA layanan
        Jadwal::where('tgl_jadwal', $request->tgl_jadwal)
            ->where('jam_mulai', $request->jam_mulai)
            ->update(['is_aktif' => $request->is_aktif]);

        return response()->json(['message' => 'Status operasional diperbarui.']);
    }
}
