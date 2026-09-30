<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_dashboard_shows_only_the_assigned_doctors_work_for_today(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $otherDoctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->for($doctorUser)->create([
            'name' => 'dr. Dokter Saya',
            'specialization' => 'Dokter Umum',
        ]);
        $otherDoctor = Doctor::factory()->for($clinic)->for($otherDoctorUser)->create([
            'name' => 'dr. Dokter Lain',
        ]);

        $calledPatient = Patient::factory()->create(['name' => 'Pasien Sudah Dipanggil']);
        $priorityPatient = Patient::factory()->create(['name' => 'Pasien Didahulukan']);
        $waitingPatient = Patient::factory()->create(['name' => 'Pasien Belum Dipanggil']);
        $activePatient = Patient::factory()->create(['name' => 'Pasien Sedang Diperiksa']);
        $completedPatient = Patient::factory()->create(['name' => 'Pasien Selesai']);

        Visit::factory()->for($calledPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->setTime(9, 0),
            'status' => 'waiting',
            'called_at' => now()->setTime(9, 10),
            'complaint' => 'KELUHAN-RAHASIA',
        ]);
        Visit::factory()->for($priorityPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->setTime(9, 15),
            'status' => 'waiting',
            'queue_priority' => 'priority',
        ]);
        Visit::factory()->for($waitingPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->setTime(9, 20),
            'status' => 'waiting',
        ]);
        Visit::factory()->for($activePatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->setTime(9, 25),
            'status' => 'in_consultation',
        ]);
        Visit::factory()->for($completedPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->setTime(9, 30),
            'status' => 'completed',
        ]);

        $otherDoctorPatient = Patient::factory()->create(['name' => 'Pasien Dokter Lain']);
        Visit::factory()->for($otherDoctorPatient)->for($clinic)->for($otherDoctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now(),
            'status' => 'waiting',
        ]);
        $yesterdayPatient = Patient::factory()->create(['name' => 'Pasien Kemarin']);
        Visit::factory()->for($yesterdayPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->subDay(),
            'status' => 'waiting',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.doctor')
            ->assertViewHas('doctor', fn (Doctor $profile): bool => $profile->is($doctor))
            ->assertViewHas('summary', [
                'total' => 5,
                'waiting' => 3,
                'ready' => 1,
                'not_called' => 2,
                'in_consultation' => 1,
                'completed' => 1,
            ])
            ->assertViewHas('queueVisits', function ($visits) use ($calledPatient, $priorityPatient, $waitingPatient): bool {
                return $visits->pluck('patient.name')->all() === [
                    $calledPatient->name,
                    $priorityPatient->name,
                    $waitingPatient->name,
                ];
            })
            ->assertViewHas('inConsultationVisits', fn ($visits): bool => $visits->count() === 1
                && $visits->first()->patient->is($activePatient))
            ->assertSee('dr. Dokter Saya')
            ->assertSee('Pasien Sudah Dipanggil')
            ->assertSee('Pasien Didahulukan')
            ->assertSee('Pasien Sedang Diperiksa')
            ->assertDontSee('Pasien Dokter Lain')
            ->assertDontSee('Pasien Kemarin')
            ->assertDontSee('KELUHAN-RAHASIA');
    }

    public function test_doctor_without_a_linked_profile_sees_an_empty_dashboard(): void
    {
        $doctorUser = User::factory()->create(['role' => 'dokter']);

        $this->actingAs($doctorUser)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.doctor')
            ->assertViewHas('doctor', null)
            ->assertViewHas('summary', [
                'total' => 0,
                'waiting' => 0,
                'ready' => 0,
                'not_called' => 0,
                'in_consultation' => 0,
                'completed' => 0,
            ])
            ->assertSee('Profil dokter belum terhubung ke akun ini')
            ->assertSee('Tidak ada pasien menunggu hari ini');
    }
}
