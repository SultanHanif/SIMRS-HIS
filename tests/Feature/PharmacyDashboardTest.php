<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharmacy_dashboard_lists_pending_prescriptions_in_oldest_first_order_and_tracks_dispensing(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create(['name' => 'dr. Farmasi']);

        $firstPatient = Patient::factory()->create(['name' => 'Pasien Antrean Pertama']);
        $firstVisit = Visit::factory()->for($firstPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-30 09:00:00',
            'status' => 'awaiting_pharmacy',
            'queue_number' => 1,
        ]);
        Examination::query()->create([
            'visit_id' => $firstVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis yang hanya untuk tenaga klinis',
            'notes' => 'Catatan klinis yang tidak perlu di dashboard farmasi.',
        ]);
        Prescription::query()->create([
            'visit_id' => $firstVisit->id,
            'prescribed_by' => $registrar->id,
            'medication_name' => 'Obat antrean pertama',
            'dosage' => '1 tablet, 3 kali sehari',
            'instructions' => 'Diminum setelah makan.',
        ]);

        $secondPatient = Patient::factory()->create(['name' => 'Pasien Antrean Kedua']);
        $secondVisit = Visit::factory()->for($secondPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-30 09:30:00',
            'status' => 'awaiting_pharmacy',
            'queue_number' => 2,
        ]);
        Prescription::query()->create([
            'visit_id' => $secondVisit->id,
            'prescribed_by' => $registrar->id,
            'medication_name' => 'Obat antrean kedua',
            'dosage' => '1 kapsul sehari',
        ]);

        $dispensedPatient = Patient::factory()->create(['name' => 'Pasien Sudah Dilayani']);
        $dispensedVisit = Visit::factory()->for($dispensedPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-30 08:00:00',
            'status' => 'awaiting_payment',
        ]);
        Prescription::query()->create([
            'visit_id' => $dispensedVisit->id,
            'prescribed_by' => $registrar->id,
            'medication_name' => 'Obat sudah diserahkan',
            'dosage' => '1 kali sehari',
            'status' => 'dispensed',
            'dispensed_by' => $pharmacist->id,
            'dispensed_at' => '2026-09-30 10:00:00',
        ]);

        $otherDayPatient = Patient::factory()->create(['name' => 'Pasien Hari Lain']);
        $otherDayVisit = Visit::factory()->for($otherDayPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-29 08:00:00',
            'status' => 'awaiting_payment',
        ]);
        Prescription::query()->create([
            'visit_id' => $otherDayVisit->id,
            'prescribed_by' => $registrar->id,
            'medication_name' => 'Obat hari lain',
            'dosage' => '1 kali sehari',
            'status' => 'dispensed',
            'dispensed_by' => $pharmacist->id,
            'dispensed_at' => '2026-09-29 09:00:00',
        ]);

        $this->actingAs($pharmacist)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.pharmacy')
            ->assertViewHas('pendingCount', 2)
            ->assertViewHas('dispensedTodayCount', 1)
            ->assertViewHas('pendingVisits', fn ($visits): bool => $visits->modelKeys() === [$firstVisit->id, $secondVisit->id])
            ->assertSeeInOrder([
                'Pasien Antrean Pertama',
                'Obat antrean pertama',
                'Pasien Antrean Kedua',
                'Obat antrean kedua',
            ])
            ->assertSee('Diminum setelah makan.')
            ->assertSee('Obat sudah diserahkan')
            ->assertSee('Workspace Farmasi')
            ->assertDontSee('Pasien Hari Lain')
            ->assertDontSee('Diagnosis yang hanya untuk tenaga klinis')
            ->assertDontSee('Catatan klinis yang tidak perlu di dashboard farmasi.');
    }

    public function test_pharmacy_dashboard_shows_an_empty_queue_and_denies_other_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.pharmacy')
            ->assertSee('Tidak ada resep yang menunggu')
            ->assertSee('Belum ada penyerahan obat yang dicatat hari ini.');

        $this->actingAs(User::factory()->create(['role' => 'administrasi']))
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.front-office')
            ->assertDontSee('Workspace Farmasi');
    }
}
