<?php

use App\Models\Booking;
use App\Models\Bookinglogs;
use App\Models\Jadwal;
use App\Models\LayananTerapi;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'pasien']);
    $this->petugas = User::factory()->create(['role' => 'petugas']);
    
    $this->layananPijat = LayananTerapi::create([
        'nama_layanan' => 'Layanan Pijat',
        'status' => 'aktif',
        'deskripsi' => 'Deskripsi Layanan Pijat',
        'durasi' => 60,
    ]);

    $this->layananBekam = LayananTerapi::create([
        'nama_layanan' => 'Layanan Bekam',
        'status' => 'aktif',
        'deskripsi' => 'Deskripsi Layanan Bekam',
        'durasi' => 60,
    ]);
});

test('it displays schedules properly when query criteria are met', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/jadwal?layanan_id={$this->layananPijat->id}&tanggal=" . now()->format('Y-m-d') . "&gender=laki-laki");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.UniqueID', $jadwal->UniqueID);
});

test('it filters out inactive schedules', function () {
    Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => false, // Inactive
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/jadwal?layanan_id={$this->layananPijat->id}&tanggal=" . now()->format('Y-m-d') . "&gender=laki-laki");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

test('it filters out locked schedules', function () {
    Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => true, // Locked
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/jadwal?layanan_id={$this->layananPijat->id}&tanggal=" . now()->format('Y-m-d') . "&gender=laki-laki");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

test('it filters out full schedules', function () {
    Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 1, // Full (jml_terjadwal >= kuota)
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/jadwal?layanan_id={$this->layananPijat->id}&tanggal=" . now()->format('Y-m-d') . "&gender=laki-laki");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

test('it filters out schedules where a booking exists for that gender at the same time and date (cross-service)', function () {
    // Schedule for Pijat at 08:00 Pria (available)
    $jadwalPijat = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    // Schedule for Bekam at 08:00 Pria (booked by user2)
    $jadwalBekam = Jadwal::create([
        'layanan_id' => $this->layananBekam->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 1,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    $user2 = User::factory()->create(['role' => 'pasien']);

    // Create booking for user2 on Bekam
    Booking::create([
        'user_id' => $user2->id,
        'jadwal_id' => $jadwalBekam->UniqueID,
        'nama_pasien' => 'Pasien Laki-laki',
        'no_hp' => '0812345678',
        'jenis_kelamin' => 'laki-laki',
        'status' => 'confirmed',
        'booking_at' => now(),
    ]);

    Sanctum::actingAs($this->user);

    // Get available schedules for Pijat. It should filter out $jadwalPijat because 
    // a booking at 08:00 for gender 'laki-laki' already exists under Bekam.
    $response = $this->getJson("/api/jadwal?layanan_id={$this->layananPijat->id}&tanggal=" . now()->format('Y-m-d') . "&gender=laki-laki");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

test('it successfully stores a booking and generates a ticket and creates logs', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/bookings', [
        'jadwal_id' => $jadwal->UniqueID,
        'nama_pasien' => 'Pasien Test',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nama_pasien', 'Pasien Test')
        ->assertJsonPath('data.status', 'confirmed');

    // Assert database updates
    $this->assertDatabaseHas('bookings', [
        'user_id' => $this->user->id,
        'jadwal_id' => $jadwal->UniqueID,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('jadwal', [
        'UniqueID' => $jadwal->UniqueID,
        'jml_terjadwal' => 1,
    ]);

    // Assert ticket generation
    $bookingId = $response->json('data.id');
    $this->assertDatabaseHas('table_tickets', [
        'booking_id' => $bookingId,
        'cek_in' => false,
    ]);

    // Assert status logs
    $this->assertDatabaseHas('booking_logs', [
        'booking_id' => $bookingId,
        'status_from' => 'pending',
        'status_to' => 'confirmed',
        'changed_by' => $this->user->id,
    ]);
});

test('it rejects bookings if schedule is inactive', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => false, // Inactive
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/bookings', [
        'jadwal_id' => $jadwal->UniqueID,
        'nama_pasien' => 'Pasien Test',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Maaf, jam operasional ini sedang dinonaktifkan (OFF).');
});

test('it rejects bookings if gender is mismatched', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'perempuan', // Female only
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/bookings', [
        'jadwal_id' => $jadwal->UniqueID,
        'nama_pasien' => 'Pasien Test',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki', // Male booking female-only
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Jadwal ini khusus untuk perempuan');
});

test('it rejects bookings if schedule is full', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 1, // Full
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/bookings', [
        'jadwal_id' => $jadwal->UniqueID,
        'nama_pasien' => 'Pasien Test',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Slot ini sudah penuh.');
});

test('it rejects bookings if a cross-service booking already exists', function () {
    // Pijat schedule
    $jadwalPijat = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 0,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    // Bekam schedule (already booked)
    $jadwalBekam = Jadwal::create([
        'layanan_id' => $this->layananBekam->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 1,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    $user2 = User::factory()->create(['role' => 'pasien']);

    Booking::create([
        'user_id' => $user2->id,
        'jadwal_id' => $jadwalBekam->UniqueID,
        'nama_pasien' => 'Pasien Bekam',
        'no_hp' => '0812345678',
        'jenis_kelamin' => 'laki-laki',
        'status' => 'confirmed',
        'booking_at' => now(),
    ]);

    Sanctum::actingAs($this->user);

    // Try to book Pijat at the same 08:00 time. Should be rejected due to existing Bekam booking.
    $response = $this->postJson('/api/bookings', [
        'jadwal_id' => $jadwalPijat->UniqueID,
        'nama_pasien' => 'Pasien Pijat',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Maaf, kuota untuk laki-laki di jam ini sudah terisi oleh layanan lain.');
});

test('it allows petugas to check-in successfully and logs status as done', function () {
    $jadwal = Jadwal::create([
        'layanan_id' => $this->layananPijat->id,
        'jk_target' => 'laki-laki',
        'tgl_jadwal' => now()->format('Y-m-d'),
        'jam_mulai' => '08:00:00',
        'jam_berakhir' => '09:00:00',
        'kuota' => 1,
        'jml_terjadwal' => 1,
        'is_aktif' => true,
        'jadwal_terkunci' => false,
    ]);

    $booking = Booking::create([
        'user_id' => $this->user->id,
        'jadwal_id' => $jadwal->UniqueID,
        'nama_pasien' => 'Pasien Test',
        'no_hp' => '089999999',
        'jenis_kelamin' => 'laki-laki',
        'status' => 'confirmed',
        'booking_at' => now(),
    ]);

    $tiket = Tiket::create([
        'booking_id' => $booking->id,
        'code_ticket' => 'TKT-CHECKIN',
        'data_qr' => 'TKT-CHECKIN',
        'cek_in' => false,
    ]);

    Sanctum::actingAs($this->petugas);

    $response = $this->postJson('/api/check-in', [
        'qr_code' => 'TKT-CHECKIN',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.booking.status', 'done');

    $this->assertDatabaseHas('table_tickets', [
        'id' => $tiket->id,
        'cek_in' => true,
        'scan_by' => $this->petugas->id,
    ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'done',
    ]);

    $this->assertDatabaseHas('booking_logs', [
        'booking_id' => $booking->id,
        'status_from' => 'confirmed',
        'status_to' => 'done',
        'changed_by' => $this->petugas->id,
    ]);
});
