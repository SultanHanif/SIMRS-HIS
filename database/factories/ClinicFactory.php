<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('POL-###')),
            'name' => fake()->unique()->randomElement(['Poli Umum', 'Poli Anak', 'Poli Gigi', 'Poli Jantung']),
            'consultation_fee' => 125000,
            'status' => 'active',
        ];
    }
}
