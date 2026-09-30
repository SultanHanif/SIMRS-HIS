<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimrsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_cannot_open_the_workspace(): void
    {
        $this->get('/')->assertOk()->assertSee('Buat administrator pertama');
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_user_can_sign_in_and_access_role_workspace(): void
    {
        $user = User::factory()->create([
            'email' => 'admisi@hospital.test',
            'password' => 'secret-password',
            'role' => 'administrasi',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SIMRS')
            ->assertSee('Workspace Admin / Front Office')
            ->assertDontSee('MedikaFlow');
    }

    public function test_inactive_staff_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@hospital.test',
            'password' => 'secret-password',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_role_middleware_denies_patient_data_to_pharmacy(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('patients.index'))
            ->assertForbidden();
    }

    public function test_visit_status_filter_returns_matching_visits_for_every_staff_role(): void
    {
        $this->seed();

        foreach ([
            'administrator@simrs.test',
            'admin@simrs.test',
            'dokter@simrs.test',
            'farmasi@simrs.test',
            'kasir@simrs.test',
        ] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->get(route('visits.index', ['status' => 'completed']))
                ->assertSee('UJI-SELESAI-001')
                ->assertDontSee('UJI-KASIR-001');
        }
    }

    public function test_visit_registration_searches_patients_by_medical_record_number(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'medical_record_number' => 'RM-CARI-0001',
            'name' => 'Pasien Dicari',
        ]);
        Patient::factory()->create([
            'medical_record_number' => 'RM-LAIN-0002',
            'name' => 'Pasien Lain',
        ]);

        $this->actingAs($registrar)
            ->get(route('visits.create', ['patient_search' => 'RM-CARI-0001']))
            ->assertSee('RM-CARI-0001')
            ->assertSee('Pasien Dicari')
            ->assertDontSee('Pasien Lain');
    }

    public function test_admission_can_register_visit_and_mismatched_doctor_clinic_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'administrasi']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $patient = Patient::factory()->create();
        $this->actingAs($user);

        $this->post(route('visits.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'general',
            'complaint' => 'Demam sejak kemarin',
        ])->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'registered_by' => $user->id,
            'status' => 'waiting',
            'queue_number' => 1,
        ]);
        $registeredVisit = Visit::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame(now()->toDateString(), $registeredVisit->queue_date->toDateString());

        $this->post(route('visits.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $otherClinic->id,
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'general',
        ])->assertSessionHasErrors('doctor_id');

        $this->assertDatabaseCount('visits', 1);
    }

    public function test_visit_registration_assigns_a_stable_sequence_per_clinic_and_visit_date(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $otherDoctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $otherDoctor = Doctor::factory()->for($otherClinic)->create(['user_id' => $otherDoctorUser->id]);
        $patient = Patient::factory()->create();

        $this->actingAs($registrar);

        foreach ([
            [$clinic, $doctor, '2026-09-30 09:00:00'],
            [$clinic, $doctor, '2026-09-30 10:00:00'],
            [$otherClinic, $otherDoctor, '2026-09-30 10:00:00'],
            [$clinic, $doctor, '2026-10-01 09:00:00'],
        ] as $index => [$selectedClinic, $selectedDoctor, $visitedAt]) {
            $payload = [
                'patient_id' => $patient->id,
                'doctor_id' => $selectedDoctor->id,
                'clinic_id' => $selectedClinic->id,
                'visited_at' => $visitedAt,
                'payment_method' => 'general',
            ];

            if ($index === 0) {
                $payload['queue_number'] = 999;
                $payload['queue_date'] = '1999-01-01';
            }

            $this->post(route('visits.store'), $payload)->assertRedirect();
        }

        $visits = Visit::query()
            ->where('patient_id', $patient->id)
            ->orderBy('id')
            ->get();

        $this->assertSame([1, 2, 1, 1], $visits->pluck('queue_number')->all());
        $this->assertSame(
            ['2026-09-30', '2026-09-30', '2026-09-30', '2026-10-01'],
            $visits->map(fn (Visit $visit): string => $visit->queue_date->toDateString())->all(),
        );
        $this->assertDatabaseHas('daily_queue_counters', [
            'clinic_id' => $clinic->id,
            'queue_date' => '2026-09-30',
            'last_number' => 2,
        ]);
    }

    public function test_unimplemented_insurance_payment_methods_are_rejected(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create();
        $patient = Patient::factory()->create();

        $this->actingAs($registrar)
            ->post(route('visits.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinic->id,
                'visited_at' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'bpjs',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('visits', 0);
        $this->assertDatabaseCount('daily_queue_counters', 0);
    }

    public function test_admission_cannot_assign_a_doctor_without_an_active_staff_account(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create();
        $patient = Patient::factory()->create();

        $this->actingAs($registrar)
            ->post(route('visits.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinic->id,
                'visited_at' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'general',
            ])
            ->assertSessionHasErrors('doctor_id');

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_outpatient_visit_moves_from_examination_through_pharmacy_to_payment(): void
    {
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $medication = Medication::query()->create([
            'name' => 'Parasetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'unit_price' => 5000,
        ]);
        $visit = Visit::factory()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'registered_by' => $registrar->id,
        ]);

        $this->actingAs($doctorUser)
            ->post(route('visits.start', $visit))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'in_consultation']);

        $this->post(route('visits.examination', $visit), [
            'diagnosis' => 'Infeksi saluran pernapasan akut',
            'diagnosis_code' => 'Z00.0',
            'notes' => 'Pasien sadar, tekanan darah stabil.',
            'systolic_pressure' => 120,
            'diastolic_pressure' => 80,
            'temperature_c' => '36.7',
            'pulse_rate' => 72,
            'weight_kg' => '64.25',
            'height_cm' => '168.50',
            'plan' => 'Kontrol sesuai kebutuhan.',
            'medication_id' => $medication->id,
            'dosage' => 'Sesuai instruksi dokter',
            'quantity' => 2,
            'instructions' => 'Diminum setelah makan.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'awaiting_pharmacy']);
        $this->assertDatabaseHas('examinations', ['visit_id' => $visit->id, 'diagnosis' => 'Infeksi saluran pernapasan akut']);
        $this->assertDatabaseHas('examinations', [
            'visit_id' => $visit->id,
            'systolic_pressure' => 120,
            'diastolic_pressure' => 80,
            'temperature_c' => 36.7,
            'pulse_rate' => 72,
            'weight_kg' => 64.25,
            'height_cm' => 168.5,
        ]);
        $this->get(route('visits.show', $visit))
            ->assertSee('Tanda-tanda vital')
            ->assertSee('120/80')
            ->assertSee('36.7')
            ->assertSee('64.25')
            ->assertSee('168.50');
        $this->assertDatabaseHas('prescriptions', [
            'visit_id' => $visit->id,
            'medication_id' => $medication->id,
            'medication_name' => 'Parasetamol 500 mg (Tablet)',
            'quantity' => 2,
            'unit_price' => 5000,
            'total_price' => 10000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('invoices', ['visit_id' => $visit->id, 'status' => 'unpaid']);
        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'amount' => $clinic->consultation_fee,
        ]);

        $this->actingAs($pharmacist)
            ->post(route('visits.dispense', $visit))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('prescriptions', ['visit_id' => $visit->id, 'status' => 'dispensed']);
        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'amount' => $clinic->consultation_fee + 10000,
        ]);

        $this->actingAs($cashier)
            ->post(route('visits.payment', $visit), [
                'payment_method' => 'cash',
                'amount_received' => $clinic->consultation_fee + 10000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'completed']);
        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'amount' => $clinic->consultation_fee + 10000,
            'status' => 'paid',
            'payment_method' => 'cash',
            'amount_received' => $clinic->consultation_fee + 10000,
            'change_amount' => 0,
            'paid_by' => $cashier->id,
        ]);
        $this->get(route('visits.show', $visit))
            ->assertSee('Lihat / cetak kuitansi')
            ->assertSee(route('visits.receipt', $visit), false);
    }

    public function test_visit_without_prescription_skips_pharmacy_in_the_journey_status(): void
    {
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $visit = Visit::factory()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
        ]);

        $this->actingAs($doctorUser)
            ->post(route('visits.start', $visit))
            ->assertSessionHasNoErrors();

        $this->post(route('visits.examination', $visit), [
            'diagnosis' => 'Pemeriksaan tanpa resep',
            'notes' => 'Tidak membutuhkan obat.',
        ])->assertSessionHasNoErrors();

        $this->get(route('visits.show', $visit))
            ->assertSee('Menunggu pembayaran')
            ->assertSeeInOrder(['Pendaftaran', 'Pemeriksaan', 'Pembayaran', 'Selesai'])
            ->assertDontSee('>Farmasi</span>', false);

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('examinations', [
            'visit_id' => $visit->id,
            'systolic_pressure' => null,
            'diastolic_pressure' => null,
            'temperature_c' => null,
            'pulse_rate' => null,
            'weight_kg' => null,
            'height_cm' => null,
        ]);
        $this->assertDatabaseMissing('prescriptions', ['visit_id' => $visit->id]);
        $this->assertDatabaseHas('invoices', ['visit_id' => $visit->id, 'status' => 'unpaid']);
    }

    public function test_only_assigned_doctor_can_start_an_examination(): void
    {
        $assignedDoctorUser = User::factory()->create(['role' => 'dokter']);
        $otherDoctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $assignedDoctorUser->id]);
        $visit = Visit::factory()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
        ]);

        $this->actingAs($otherDoctorUser)
            ->post(route('visits.start', $visit))
            ->assertForbidden();

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'waiting']);
    }
}
