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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('jadwal_id');
            $table->foreign('jadwal_id')->references('UniqueID')->on('jadwal')->onDelete('cascade');
            $table->string('nama_pasien', 60);
            $table->string('no_hp', 20)->nullable();
            $table->enum('jenis_kelamin', ['laki-laki', 'perempuan']);
            $table->boolean('jk_cocok')->default(true);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'done'])->default('pending');
            $table->timestamp('booking_at')->useCurrent();
            $table->timestamp('confirmasi_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
