<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Jadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['laki-laki', 'perempuan']);
        $status = fake()->randomElement(['confirmed', 'done', 'cancelled', 'cancelled_by_admin']);
        
        $bookingAt = fake()->dateTimeBetween('-2 months', 'now');
        $confirmasiAt = in_array($status, ['confirmed', 'done']) ? fake()->dateTimeBetween($bookingAt, 'now') : null;

        return [
            'user_id' => User::factory(), // Akan ditimpa di seeder
            // 'jadwal_id' akan di set manual di seeder agar match dengan gender
            'nama_pasien' => substr(fake()->name($gender === 'laki-laki' ? 'male' : 'female'), 0, 60),
            'no_hp' => '08' . fake()->numerify('##########'),
            'jenis_kelamin' => $gender,
            'jk_cocok' => true,
            'status' => $status,
            'booking_at' => $bookingAt,
            'confirmasi_at' => $confirmasiAt,
        ];
    }
}
