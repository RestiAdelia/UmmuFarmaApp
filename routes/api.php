<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\LayananController;
use App\Http\Controllers\Api\PetugasController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ── Public Routes ──────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Katalog layanan — Akses Umum (Tanpa Login)
Route::get('/layanan', [LayananController::class, 'index']);
Route::get('/layanan/{id}', [LayananController::class, 'show']);

// ── Protected Routes (Authenticated) ───────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/update-password', [AuthController::class, 'updatePassword']);

    // ── Jadwal & Booking ──────────────────────────────────────────
    Route::get('/jadwal', [JadwalController::class, 'index']);
    Route::get('/jadwal/mingguan', [JadwalController::class, 'getJadwalMingguan']);
    Route::get('/schedules/available', [JadwalController::class, 'getAvailableSchedules']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/store', [BookingController::class, 'storeBooking']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    // ── Petugas Only ────────────────────────────────────────────────
    Route::middleware('role:petugas,admin')->group(function () {
        Route::post('/check-in', [BookingController::class, 'checkIn']);
    });

    // ── Admin Only ──────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // Kelola layanan
        Route::post('/layanan',       [LayananController::class, 'store']);
        Route::put('/layanan/{id}',   [LayananController::class, 'update']);
        Route::delete('/layanan/{id}', [LayananController::class, 'destroy']);
         Route::get('/admin/jadwal-operasional', [JadwalController::class, 'getJadwalOperasional']);

        // Kelola petugas
        Route::get('/petugas',         [PetugasController::class, 'index']);
        Route::post('/petugas',        [PetugasController::class, 'store']);
        Route::put('/petugas/{id}',    [PetugasController::class, 'update']);
        Route::delete('/petugas/{id}', [PetugasController::class, 'destroy']);

        // Kelola jadwal
        Route::post('/jadwal/toggle', [JadwalController::class, 'toggleOperasional']);
    });

    // Note: Other routes for Jadwal, Booking, etc. can be added here as controllers are ready.
});
