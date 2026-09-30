<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientVisitHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_can_view_operational_history_for_a_patient_without_clinical_notes(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create([
            'medical_record_number' => 'RM-HISTORY-001',
            'name' => 'Pasien Riwayat',
        ]);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-UMUM',
            'name' => 'Poli Umum',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create([
            'name' => 'dr. Budi',
            'specialization' => 'Dokter Umum',
        ]);
        $visit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'visit_number' => 'RJ-HISTORY-001',
            'visited_at' => '2026-09-20 09:15:00',
            'queue_date' => '2026-09-20',
            'queue_number' => 8,
            'status' => 'completed',
            'complaint' => 'KELUHAN-RAHASIA-001',
        ]);
        Examination::query()->create([
            'visit_id' => $visit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'DIAGNOSIS-RAHASIA-001',
            'notes' => 'CATATAN-KLINIS-RAHASIA-001',
        ]);

        $this->actingAs($registrar)
            ->get(route('patients.visits', $patient))
            ->assertSee('Riwayat pelayanan')
            ->assertSee('Pasien Riwayat')
            ->assertSee('RM-HISTORY-001')
            ->assertSee('20/09/2026 09:15')
            ->assertSee('Antrean 008')
            ->assertSee('POL-UMUM')
            ->assertSee('Poli Umum')
            ->assertSee('dr. Budi')
            ->assertSee('Selesai')
            ->assertSee('RJ-HISTORY-001')
            ->assertDontSee('KELUHAN-RAHASIA-001')
            ->assertDontSee('DIAGNOSIS-RAHASIA-001')
            ->assertDontSee('CATATAN-KLINIS-RAHASIA-001');
    }

    public function test_patient_list_links_to_the_visit_history(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create(['name' => 'Pasien Daftar']);

        $this->actingAs($registrar)
            ->get(route('patients.index'))
            ->assertSee('Riwayat')
            ->assertSee(route('patients.visits', $patient), false);
    }

    public function test_patient_visit_history_requires_front_office_access(): void
    {
        $patient = Patient::factory()->create();

        $this->get(route('patients.visits', $patient))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('patients.visits', $patient))
            ->assertForbidden();
    }

    public function test_visit_history_shows_an_empty_state_when_the_patient_has_no_visits(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create();

        $this->actingAs($registrar)
            ->get(route('patients.visits', $patient))
            ->assertSee('Belum ada riwayat kunjungan');
    }
}
