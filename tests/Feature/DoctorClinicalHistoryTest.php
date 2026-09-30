<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorClinicalHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_doctor_can_view_recent_prior_examinations_for_the_current_patient(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $otherClinic = Clinic::factory()->create(['name' => 'Poli Anak']);
        $doctor = Doctor::factory()->for($clinic)->for($doctorUser)->create(['name' => 'dr. Penanggung Jawab']);
        $previousDoctor = Doctor::factory()->for($otherClinic)->create(['name' => 'dr. Dokter Sebelumnya']);
        $patient = Patient::factory()->create(['name' => 'Pasien Dengan Riwayat']);

        $olderVisit = Visit::factory()->for($patient)->for($otherClinic)->for($previousDoctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-08-10 09:00:00',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $olderVisit->id,
            'doctor_id' => $previousDoctor->id,
            'diagnosis_code' => 'A10.1',
            'diagnosis' => 'Diagnosis terdahulu pertama',
            'notes' => 'Catatan terdahulu pertama',
            'plan' => 'Rencana terdahulu pertama',
        ]);

        $recentVisit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-10 10:30:00',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $recentVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis_code' => 'B20.2',
            'diagnosis' => 'Diagnosis terdahulu terbaru',
            'notes' => '<script>alert("riwayat")</script>',
            'plan' => 'Rencana terdahulu terbaru',
            'systolic_pressure' => 118,
            'diastolic_pressure' => 76,
            'temperature_c' => '36.8',
        ]);

        $currentVisit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-30 10:00:00',
            'status' => 'in_consultation',
        ]);
        Examination::query()->create([
            'visit_id' => $currentVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis kunjungan berjalan',
            'notes' => 'Catatan kunjungan berjalan',
        ]);

        $futureVisit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-10-02 10:00:00',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $futureVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis tanggal mendatang',
            'notes' => 'Catatan tanggal mendatang',
        ]);

        $otherPatient = Patient::factory()->create(['name' => 'Pasien Berbeda']);
        $otherPatientVisit = Visit::factory()->for($otherPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-20 10:00:00',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $otherPatientVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis pasien berbeda',
            'notes' => 'Catatan pasien berbeda',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('visits.show', $currentVisit))
            ->assertSee('Riwayat pemeriksaan sebelumnya')
            ->assertSee('Diagnosis terdahulu terbaru')
            ->assertSee('Rencana terdahulu terbaru')
            ->assertSee('118/76 mmHg')
            ->assertSee('36.8 °C')
            ->assertSee('Diagnosis terdahulu pertama')
            ->assertSee('dr. Dokter Sebelumnya')
            ->assertSee('Poli Anak')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("riwayat")</script>', false)
            ->assertDontSee('Diagnosis tanggal mendatang')
            ->assertDontSee('Diagnosis pasien berbeda');
    }

    public function test_unassigned_doctor_cannot_view_a_patients_clinical_history(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $assignedDoctor = Doctor::factory()->create();
        $visit = Visit::factory()->for($assignedDoctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $visit->id,
            'doctor_id' => $assignedDoctor->id,
            'diagnosis' => 'Diagnosis rahasia',
            'notes' => 'Catatan rahasia',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'dokter']))
            ->get(route('visits.show', $visit))
            ->assertForbidden();
    }

    public function test_staff_without_clinical_access_cannot_view_vital_signs(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $previousVisit = Visit::factory()->for($patient)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-01 10:00:00',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $previousVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis klinis terbatas',
            'notes' => 'Catatan klinis terbatas',
            'systolic_pressure' => 120,
            'diastolic_pressure' => 80,
        ]);
        $currentVisit = Visit::factory()->for($patient)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-30 10:00:00',
            'status' => 'completed',
        ]);
        Prescription::query()->create([
            'visit_id' => $currentVisit->id,
            'prescribed_by' => $registrar->id,
            'medication_name' => 'Obat resep',
            'dosage' => 'Sesuai instruksi dokter',
        ]);
        Invoice::query()->create([
            'visit_id' => $currentVisit->id,
            'amount' => 100000,
        ]);

        $this->actingAs($registrar)
            ->get(route('visits.show', $currentVisit))
            ->assertDontSee('Riwayat pemeriksaan sebelumnya')
            ->assertDontSee('Diagnosis klinis terbatas')
            ->assertDontSee('Catatan klinis terbatas')
            ->assertDontSee('120/80 mmHg');

        foreach (['farmasi', 'kasir'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('visits.show', $currentVisit))
                ->assertDontSee('Riwayat pemeriksaan sebelumnya')
                ->assertDontSee('Diagnosis klinis terbatas')
                ->assertDontSee('Catatan klinis terbatas')
                ->assertDontSee('120/80 mmHg');
        }
    }
}
