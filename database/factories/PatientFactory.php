<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_number' => 'RM-'.fake()->unique()->numerify('##########'),
            'national_id' => null,
            'name' => fake()->name(),
            'date_of_birth' => fake()->date(),
            'sex' => fake()->randomElement(['Perempuan', 'Laki-laki']),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->streetAddress(),
        ];
    }
}
