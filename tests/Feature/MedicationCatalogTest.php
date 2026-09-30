<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Medication;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharmacy_can_create_and_deactivate_a_medication_and_duplicates_are_rejected(): void
    {
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $createPayload = [
            'name' => 'Parasetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'unit_price' => 2500,
        ];

        $this->actingAs($pharmacist)
            ->get(route('pharmacy.medications.index'))
            ->assertSee('Katalog obat')
            ->assertSee('Tambah obat')
            ->assertSee('belum mencatat stok');

        $this->post(route('pharmacy.medications.store'), $createPayload)
            ->assertSessionHasNoErrors();

        $medication = Medication::query()->firstOrFail();
        $this->assertDatabaseHas('medications', [
            'id' => $medication->id,
            'name' => 'Parasetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'unit_price' => 2500,
            'status' => 'active',
        ]);

        $this->post(route('pharmacy.medications.store'), $createPayload)
            ->assertSessionHasErrors('name');

        $this->put(route('pharmacy.medications.update', $medication), [
            ...$createPayload,
            'status' => 'inactive',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('medications', ['id' => $medication->id, 'status' => 'inactive']);
    }

    public function test_pharmacy_can_search_the_catalog_by_name_strength_and_dosage_form(): void
    {
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        Medication::query()->create([
            'name' => 'Parasetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
        ]);
        Medication::query()->create([
            'name' => 'Amoksisilin',
            'strength' => '250 mg',
            'dosage_form' => 'Kapsul',
        ]);
        Medication::query()->create([
            'name' => 'Vitamin C',
            'strength' => '100 mg',
            'dosage_form' => 'Kaplet',
        ]);

        $this->actingAs($pharmacist)
            ->get(route('pharmacy.medications.index', ['search' => 'Parasetamol']))
            ->assertSee('Parasetamol 500 mg (Tablet)')
            ->assertDontSee('Amoksisilin 250 mg (Kapsul)');

        $this->get(route('pharmacy.medications.index', ['search' => '250 mg']))
            ->assertSee('Amoksisilin 250 mg (Kapsul)')
            ->assertDontSee('Parasetamol 500 mg (Tablet)');

        $this->get(route('pharmacy.medications.index', ['search' => 'Kaplet']))
            ->assertSee('Vitamin C 100 mg (Kaplet)')
            ->assertDontSee('Amoksisilin 250 mg (Kapsul)');

        $this->get(route('pharmacy.medications.index', ['search' => 'tidak-ada']))
            ->assertSee('Obat tidak ditemukan');
    }

    public function test_doctor_can_select_only_active_catalog_medicines_for_a_new_prescription(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $visit = Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'in_consultation',
        ]);
        $activeMedication = Medication::query()->create([
            'name' => 'Amoksisilin',
            'strength' => '500 mg',
            'dosage_form' => 'Kapsul',
            'unit_price' => 8000,
        ]);
        Medication::query()->create([
            'name' => 'Ibuprofen',
            'strength' => '200 mg',
            'dosage_form' => 'Tablet',
        ]);
        $inactiveMedication = Medication::query()->create([
            'name' => 'Obat Nonaktif',
            'strength' => '10 mg',
            'dosage_form' => 'Tablet',
            'status' => 'inactive',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('visits.show', [$visit, 'medication_search' => 'Kapsul']))
            ->assertSee('Amoksisilin 500 mg (Kapsul)')
            ->assertDontSee('Ibuprofen 200 mg (Tablet)')
            ->assertDontSee('Obat Nonaktif 10 mg (Tablet)');

        $this->post(route('visits.examination', $visit), [
            'diagnosis' => 'Infeksi',
            'notes' => 'Perlu terapi.',
            'medication_id' => $activeMedication->id,
            'dosage' => '1 kapsul, 3 kali sehari',
            'quantity' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prescriptions', [
            'visit_id' => $visit->id,
            'medication_id' => $activeMedication->id,
            'medication_name' => 'Amoksisilin 500 mg (Kapsul)',
            'quantity' => 3,
            'unit_price' => 8000,
            'total_price' => 24000,
        ]);
    }

    public function test_doctor_cannot_prescribe_a_deactivated_catalog_medicine(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);
        $visit = Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'in_consultation',
        ]);
        $inactiveMedication = Medication::query()->create([
            'name' => 'Obat Nonaktif',
            'strength' => '10 mg',
            'dosage_form' => 'Tablet',
            'status' => 'inactive',
        ]);

        $this->actingAs($doctorUser)
            ->post(route('visits.examination', $visit), [
                'diagnosis' => 'Pemeriksaan',
                'notes' => 'Catatan.',
                'medication_id' => $inactiveMedication->id,
                'dosage' => '1 kali sehari',
            ])
            ->assertSessionHasErrors('medication_id');

        $this->assertDatabaseMissing('prescriptions', ['visit_id' => $visit->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'in_consultation']);
    }

    public function test_only_pharmacy_or_super_admin_can_manage_the_medication_catalog(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->get(route('pharmacy.medications.index'))
            ->assertForbidden();

        $this->post(route('pharmacy.medications.store'), [
            'name' => 'Obat Tidak Diizinkan',
            'strength' => '1 mg',
            'dosage_form' => 'Tablet',
            'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseCount('medications', 0);
    }
}
