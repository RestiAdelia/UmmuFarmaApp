<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->uuid('UniqueID')->unique();
            $table->foreignId('layanan_id')->constrained('layanan_terapis')->onDelete('cascade');
            $table->enum('jk_target', ['laki-laki', 'perempuan', 'semua'])->default('semua');
            $table->date('tgl_jadwal');
            $table->time('jam_mulai');
            $table->time('jam_berakhir');
            $table->unsignedInteger('kuota')->default(1); // Set default 1 sesuai permintaan
            $table->unsignedInteger('jml_terjadwal')->default(0);
            $table->boolean('is_aktif')->default(true); // Untuk jam operasional ON/OFF
            $table->boolean('jadwal_terkunci')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            Schema::dropIfExists('jadwal');
        });
    }
};
