<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePatientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_can_open_the_patient_edit_form(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create([
            'medical_record_number' => 'RM-EDIT-0001',
            'name' => 'Pasien Sebelum',
        ]);

        $this->actingAs($registrar)
            ->get(route('patients.edit', $patient))
            ->assertOk()
            ->assertSee('Edit data pasien')
            ->assertSee('RM-EDIT-0001')
            ->assertSee('Pasien Sebelum')
            ->assertSee(route('patients.update', $patient), false);
    }

    public function test_front_office_can_update_patient_details_without_changing_the_medical_record_number(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create([
            'medical_record_number' => 'RM-EDIT-0002',
            'national_id' => '3174000000000001',
            'name' => 'Nama Lama',
        ]);

        $this->actingAs($registrar)
            ->put(route('patients.update', $patient), [
                'medical_record_number' => 'RM-DIUBAH-001',
                'national_id' => '3174000000000001',
                'name' => 'Nama Baru',
                'date_of_birth' => '1990-05-12',
                'sex' => 'Perempuan',
                'phone' => '081234567890',
                'address' => 'Alamat Baru',
            ])
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('success', 'Data pasien Nama Baru berhasil diperbarui.');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'medical_record_number' => 'RM-EDIT-0002',
            'national_id' => '3174000000000001',
            'name' => 'Nama Baru',
            'sex' => 'Perempuan',
            'phone' => '081234567890',
            'address' => 'Alamat Baru',
        ]);
        $this->assertSame('1990-05-12', $patient->refresh()->date_of_birth?->toDateString());
    }

    public function test_front_office_cannot_assign_another_patients_national_id(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create([
            'national_id' => '3174000000000001',
            'name' => 'Nama Tetap',
        ]);
        Patient::factory()->create(['national_id' => '3174000000000002']);

        $this->actingAs($registrar)
            ->from(route('patients.edit', $patient))
            ->put(route('patients.update', $patient), [
                'national_id' => '3174000000000002',
                'name' => 'Nama Tidak Tersimpan',
                'sex' => 'Perempuan',
            ])
            ->assertSessionHasErrors([
                'national_id' => 'NIK sudah digunakan oleh pasien lain.',
            ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'national_id' => '3174000000000001',
            'name' => 'Nama Tetap',
        ]);
    }

    public function test_front_office_cannot_save_invalid_patient_details(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create(['name' => 'Nama Sebelum']);

        $this->actingAs($registrar)
            ->from(route('patients.edit', $patient))
            ->put(route('patients.update', $patient), [
                'name' => '',
                'date_of_birth' => today()->addDay()->toDateString(),
                'sex' => 'Tidak diketahui',
            ])
            ->assertSessionHasErrors(['name', 'date_of_birth', 'sex']);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Nama Sebelum',
        ]);
    }

    public function test_patient_edit_requires_front_office_access(): void
    {
        $patient = Patient::factory()->create();

        $this->get(route('patients.edit', $patient))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('patients.edit', $patient))
            ->assertForbidden();

        $this->put(route('patients.update', $patient), [
            'name' => 'Tidak Diizinkan',
            'sex' => 'Perempuan',
        ])->assertForbidden();

        $this->assertDatabaseMissing('patients', ['name' => 'Tidak Diizinkan']);
    }
}
