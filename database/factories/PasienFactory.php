<?php

namespace Database\Factories;

use App\Models\Pasien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pasien>
 */
class PasienFactory extends Factory
{
    protected $model = Pasien::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['laki-laki', 'perempuan']);
        
        return [
            'user_id' => User::factory(),
            'nama_lengkap' => substr(fake()->name($gender === 'laki-laki' ? 'male' : 'female'), 0, 60),
            'jenis_kelamin' => $gender,
            'no_hp' => '08' . fake()->numerify('##########'),
            'status' => fake()->randomElement(['aktif', 'aktif', 'aktif', 'pending', 'ditolak']),
        ];
    }
}
