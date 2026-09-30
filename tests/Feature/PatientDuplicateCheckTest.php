<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDuplicateCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_finds_an_existing_patient_by_national_id_without_exposing_sensitive_fields(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'medical_record_number' => 'RM-DUP-0001',
            'national_id' => '3174000000000001',
            'name' => 'Pasien Terdaftar',
            'phone' => '081234567890',
            'address' => 'Alamat Pribadi',
        ]);

        $this->actingAs($registrar)
            ->getJson(route('patients.possible-duplicates', ['national_id' => '3174000000000001']))
            ->assertOk()
            ->assertJsonPath('matches.0.medical_record_number', 'RM-DUP-0001')
            ->assertJsonPath('matches.0.name', 'Pasien Terdaftar')
            ->assertJsonPath('matches.0.matched_by.0', 'NIK')
            ->assertJsonMissingPath('matches.0.national_id')
            ->assertJsonMissingPath('matches.0.phone')
            ->assertJsonMissingPath('matches.0.address');
    }

    public function test_front_office_finds_an_existing_patient_by_case_insensitive_name_and_birth_date(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'medical_record_number' => 'RM-DUP-0002',
            'name' => 'Siti Aminah',
            'date_of_birth' => '1990-05-12',
        ]);

        $this->actingAs($registrar)
            ->getJson(route('patients.possible-duplicates', [
                'name' => 'SITI AMINAH',
                'date_of_birth' => '1990-05-12',
            ]))
            ->assertOk()
            ->assertJsonPath('matches.0.medical_record_number', 'RM-DUP-0002')
            ->assertJsonPath('matches.0.matched_by.0', 'Nama dan tanggal lahir');
    }

    public function test_patients_with_the_same_name_but_a_different_birth_date_are_not_reported_as_duplicates(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'name' => 'Siti Aminah',
            'date_of_birth' => '1990-05-12',
        ]);

        $this->actingAs($registrar)
            ->getJson(route('patients.possible-duplicates', [
                'name' => 'Siti Aminah',
                'date_of_birth' => '1991-05-12',
            ]))
            ->assertOk()
            ->assertExactJson(['matches' => []]);
    }

    public function test_front_office_can_intentionally_register_a_different_patient_with_the_same_name_and_birth_date(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'name' => 'Siti Aminah',
            'date_of_birth' => '1990-05-12',
            'national_id' => '3174000000000001',
        ]);

        $this->actingAs($registrar)
            ->post(route('patients.store'), [
                'name' => 'Siti Aminah',
                'date_of_birth' => '1990-05-12',
                'national_id' => '3174000000000002',
                'sex' => 'Perempuan',
            ])
            ->assertRedirect(route('patients.index'));

        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseHas('patients', [
            'name' => 'Siti Aminah',
            'national_id' => '3174000000000002',
        ]);
    }

    public function test_front_office_cannot_create_a_second_patient_with_the_same_national_id(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        Patient::factory()->create([
            'national_id' => '3174000000000001',
        ]);

        $this->actingAs($registrar)
            ->from(route('patients.create'))
            ->post(route('patients.store'), [
                'name' => 'Pasien Duplikat',
                'national_id' => '3174000000000001',
                'sex' => 'Perempuan',
            ])
            ->assertSessionHasErrors([
                'national_id' => 'NIK sudah terdaftar. Cari dan gunakan rekam medis pasien yang sudah ada.',
            ]);

        $this->assertDatabaseCount('patients', 1);
    }

    public function test_possible_duplicate_lookup_requires_front_office_access(): void
    {
        $this->getJson(route('patients.possible-duplicates', ['national_id' => '3174000000000001']))
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->getJson(route('patients.possible-duplicates', ['national_id' => '3174000000000001']))
            ->assertForbidden();
    }

    public function test_possible_duplicate_lookup_rejects_invalid_date_of_birth(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->getJson(route('patients.possible-duplicates', [
                'name' => 'Siti Aminah',
                'date_of_birth' => 'not-a-date',
            ]))
            ->assertJsonValidationErrors('date_of_birth');
    }

    public function test_new_patient_form_includes_the_duplicate_warning_workspace(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->get(route('patients.create'))
            ->assertSee('data-patient-duplicate-check', false)
            ->assertSee('data-duplicate-matches', false)
            ->assertSee(route('patients.possible-duplicates'), false);
    }
}
