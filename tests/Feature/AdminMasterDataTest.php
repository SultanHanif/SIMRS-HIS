<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_staff_account_with_an_assigned_role(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Dokter Klinik',
                'email' => 'doctor@hospital.test',
                'role' => 'dokter',
                'password' => 'HospitalSecurePass123!',
                'password_confirmation' => 'HospitalSecurePass123!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Dokter Klinik',
            'email' => 'doctor@hospital.test',
            'role' => 'dokter',
            'is_active' => true,
        ]);
        $this->assertTrue(Hash::check(
            'HospitalSecurePass123!',
            User::query()->where('email', 'doctor@hospital.test')->value('password'),
        ));
    }

    public function test_non_administrator_cannot_manage_staff_or_clinic_data(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $staff = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($registrar)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->put(route('admin.users.update', $staff), [
            'name' => 'Nama Tidak Diizinkan',
            'email' => $staff->email,
            'role' => $staff->role,
            'is_active' => true,
        ])->assertForbidden();

        $this->post(route('admin.clinics.store'), [
            'code' => 'POL-UMUM',
            'name' => 'Poli Umum',
            'consultation_fee' => 125000,
            'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseCount('clinics', 0);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => $staff->name]);
    }

    public function test_administrator_can_change_their_own_name(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Administrator SIMRS',
                'email' => $admin->email,
                'role' => $admin->role,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Administrator SIMRS',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_administrator_can_open_each_configuration_workspace(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertSee('Akun petugas');

        $this->get(route('admin.clinics.index'))
            ->assertSee('Tambah poli');

        $this->get(route('admin.doctors.index'))
            ->assertSee('Profil dokter');
    }

    public function test_administrator_can_create_a_clinic_with_a_real_consultation_fee(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.clinics.store'), [
                'code' => 'POL-UMUM',
                'name' => 'Poli Umum',
                'consultation_fee' => 125000,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clinics', [
            'code' => 'POL-UMUM',
            'name' => 'Poli Umum',
            'consultation_fee' => 125000,
        ]);
    }

    public function test_administrator_can_link_a_doctor_role_account_to_an_active_clinic(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $clinic = Clinic::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.doctors.store'), [
                'user_id' => $doctorUser->id,
                'clinic_id' => $clinic->id,
                'name' => 'dr. Klinik',
                'specialization' => 'Dokter Umum',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('doctors', [
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'name' => 'dr. Klinik',
        ]);
    }

    public function test_administrator_cannot_remove_their_own_administrator_access(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'kasir',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'super_admin', 'is_active' => true]);
    }

    public function test_administrator_can_disable_another_administrator_without_losing_access(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $anotherAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $anotherAdmin), [
                'name' => $anotherAdmin->name,
                'email' => $anotherAdmin->email,
                'role' => 'kasir',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id, 'role' => 'kasir', 'is_active' => false]);
    }

    public function test_deactivated_account_is_forced_to_sign_in_again(): void
    {
        $staff = User::factory()->create(['role' => 'administrasi', 'is_active' => false]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_clinics_without_a_configured_fee_cannot_be_used_to_register_visits(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['consultation_fee' => 0]);
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        $doctor = Doctor::factory()->for($clinic)->create(['user_id' => $doctorUser->id]);

        $this->actingAs($registrar)
            ->post(route('visits.store'), [
                'patient_id' => Patient::factory()->create()->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinic->id,
                'visited_at' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'general',
            ])
            ->assertSessionHasErrors([
                'clinic_id' => 'Tarif konsultasi poli belum diatur oleh administrator.',
            ]);

        $this->assertDatabaseCount('visits', 0);
    }
}
