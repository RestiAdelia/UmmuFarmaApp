<?php

namespace Database\Factories;

use App\Models\Tiket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tiket>
 */
class TiketFactory extends Factory
{
    protected $model = Tiket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codeTicket = 'TKT-' . strtoupper(Str::random(10));
        return [
            // 'booking_id' akan di-set saat seeder
            'code_ticket' => $codeTicket,
            'data_qr' => json_encode(['ticket_code' => $codeTicket]),
            'cek_in' => false,
            'scan_at' => null,
            'scan_by' => null,
        ];
    }
}
