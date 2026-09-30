<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'clinic_id' => Clinic::factory(),
            'name' => fake()->name(),
            'specialization' => fake()->randomElement(['Dokter Umum', 'Spesialis Anak', 'Spesialis Penyakit Dalam']),
            'status' => 'active',
        ];
    }
}
