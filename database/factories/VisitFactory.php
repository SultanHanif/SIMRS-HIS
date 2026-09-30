<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clinic = Clinic::factory();

        return [
            'visit_number' => 'RJ-'.strtoupper(Str::random(10)),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory()->for($clinic),
            'clinic_id' => $clinic,
            'registered_by' => User::factory(),
            'visited_at' => now(),
            'visit_type' => 'outpatient',
            'payment_method' => 'general',
            'complaint' => 'Kontrol rutin',
            'status' => 'waiting',
        ];
    }
}
