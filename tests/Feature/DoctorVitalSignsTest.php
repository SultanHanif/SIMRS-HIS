<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVitalSignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_vital_signs_are_rejected_without_saving_the_examination(): void
    {
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $visit = Visit::factory()->for($clinic)->create([
            'doctor_id' => $doctor->id,
            'status' => 'in_consultation',
        ]);

        $this->actingAs($doctorUser)
            ->post(route('visits.examination', $visit), [
                'diagnosis' => 'Pemeriksaan umum',
                'notes' => 'Catatan pemeriksaan.',
                'systolic_pressure' => -1,
                'diastolic_pressure' => -1,
                'temperature_c' => 100,
                'pulse_rate' => -1,
                'weight_kg' => -1,
                'height_cm' => -1,
            ])
            ->assertSessionHasErrors([
                'systolic_pressure',
                'diastolic_pressure',
                'temperature_c',
                'pulse_rate',
                'weight_kg',
                'height_cm',
            ]);

        $this->assertDatabaseMissing('examinations', ['visit_id' => $visit->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'in_consultation']);
    }
}
