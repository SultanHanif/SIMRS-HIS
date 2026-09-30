<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\HospitalDemoSeeder;
use Database\Seeders\WorkflowDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class HospitalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_role_accounts_and_a_complete_outpatient_workflow(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('visits', 5);
        $this->assertDatabaseCount('clinics', 5);
        $this->assertDatabaseCount('doctors', 5);
        $this->assertDatabaseCount('patients', 8);
        $this->assertDatabaseHas('users', ['email' => 'administrator@simrs.test', 'role' => 'super_admin']);
        $this->assertDatabaseHas('users', ['email' => 'admin@simrs.test', 'role' => 'administrasi']);
        $this->assertDatabaseHas('users', ['email' => 'dokter@simrs.test', 'role' => 'dokter']);
        $this->assertDatabaseHas('users', ['email' => 'farmasi@simrs.test', 'role' => 'farmasi']);
        $this->assertDatabaseHas('users', ['email' => 'kasir@simrs.test', 'role' => 'kasir']);
        $this->assertDatabaseHas('patients', [
            'medical_record_number' => 'UJI-RM-2026-0001',
            'national_id' => null,
            'address' => 'Alamat simulasi, Kota Contoh',
        ]);
        $this->assertDatabaseHas('doctors', [
            'name' => 'dr. Arif Pratama',
            'user_id' => User::query()->where('email', 'dokter@simrs.test')->value('id'),
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-ALUR-001', 'status' => 'waiting']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-FARMASI-001', 'status' => 'awaiting_pharmacy']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-KASIR-001', 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-SELESAI-001', 'status' => 'completed']);
        $this->assertDatabaseHas('prescriptions', [
            'medication_name' => 'Parasetamol 500 mg (SIMULASI)',
            'status' => 'pending',
        ]);

        $this->assertTrue(Hash::check(
            'password',
            User::query()->where('email', 'dokter@simrs.test')->value('password'),
        ));
        $this->assertTrue(Hash::check(
            'password',
            User::query()->where('email', 'administrator@simrs.test')->value('password'),
        ));
    }

    public function test_database_seeder_can_be_rerun_without_duplicating_records(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $this->seed();

        Visit::query()->whereIn('visit_number', [
            'UJI-ALUR-001',
            'UJI-DOKTER-001',
            'UJI-FARMASI-001',
            'UJI-KASIR-001',
            'UJI-SELESAI-001',
        ])->update(['status' => 'completed']);
        Prescription::query()->update(['status' => 'dispensed']);
        Invoice::query()->update(['status' => 'paid']);

        $this->seed();

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('clinics', 5);
        $this->assertDatabaseCount('doctors', 5);
        $this->assertDatabaseCount('patients', 8);
        $this->assertDatabaseCount('visits', 5);
        $this->assertDatabaseCount('prescriptions', 3);
        $this->assertDatabaseHas('visits', [
            'visit_number' => 'UJI-ALUR-001',
            'queue_number' => 1,
        ]);
        $this->assertDatabaseHas('visits', [
            'visit_number' => 'UJI-SELESAI-001',
            'queue_number' => 5,
        ]);
        $this->assertDatabaseHas('daily_queue_counters', [
            'last_number' => 5,
        ]);
        $this->assertSame(
            '2026-09-30',
            Visit::query()->where('visit_number', 'UJI-ALUR-001')->firstOrFail()->queue_date->toDateString(),
        );
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-ALUR-001', 'status' => 'waiting']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-DOKTER-001', 'status' => 'in_consultation']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-FARMASI-001', 'status' => 'awaiting_pharmacy']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-KASIR-001', 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('visits', ['visit_number' => 'UJI-SELESAI-001', 'status' => 'completed']);
        $this->assertDatabaseHas('prescriptions', [
            'medication_name' => 'Parasetamol 500 mg (SIMULASI)',
            'status' => 'pending',
            'dispensed_by' => null,
            'dispensed_at' => null,
        ]);
        $this->assertDatabaseHas('invoices', [
            'status' => 'unpaid',
            'paid_by' => null,
            'paid_at' => null,
        ]);
    }

    public function test_database_seeder_preserves_names_changed_by_an_administrator(): void
    {
        $this->seed();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $staff = User::query()->where('email', 'admin@simrs.test')->firstOrFail();
        $updatedName = 'Nama Front Office Pilihan Rumah Sakit';

        $this->actingAs($admin)
            ->put(route('admin.users.update', $staff), [
                'name' => $updatedName,
                'email' => $staff->email,
                'role' => $staff->role,
                'is_active' => true,
            ])
            ->assertRedirect();

        app(WorkflowDemoSeeder::class)->run();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => $updatedName,
            'email' => 'admin@simrs.test',
        ]);
    }

    public function test_role_accounts_can_authenticate_with_their_assigned_permissions(): void
    {
        $this->seed();

        foreach ([
            'administrator@simrs.test',
            'admin@simrs.test',
            'dokter@simrs.test',
            'farmasi@simrs.test',
            'kasir@simrs.test',
        ] as $email) {
            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard'));

            $this->post(route('logout'));
        }
    }

    public function test_login_uses_simrs_brand_hides_presentation_accounts_and_offers_password_visibility_control(): void
    {
        $this->seed();
        $this->app->instance('env', 'local');

        $this->get(route('login'))
            ->assertSee('SIMRS')
            ->assertDontSee('MedikaFlow')
            ->assertDontSee('Akun presentasi lokal')
            ->assertDontSee('admin@simrs.test')
            ->assertDontSee('php artisan db:seed')
            ->assertSee('data-password-toggle', false)
            ->assertSee('Tampilkan kata sandi')
            ->assertDontSee('Kata sandi akun yang dibuat seeder')
            ->assertDontSee('password</code>', false);
    }

    public function test_outpatient_visit_can_complete_across_staff_roles(): void
    {
        $this->seed();

        $visit = Visit::query()->where('visit_number', 'UJI-ALUR-001')->firstOrFail();
        $doctor = User::query()->where('email', 'dokter@simrs.test')->firstOrFail();
        $pharmacist = User::query()->where('email', 'farmasi@simrs.test')->firstOrFail();
        $cashier = User::query()->where('email', 'kasir@simrs.test')->firstOrFail();
        $medication = Medication::query()->create([
            'name' => 'Obat simulasi',
            'strength' => '250 mg',
            'dosage_form' => 'Tablet',
        ]);

        $this->actingAs($doctor)
            ->post(route('visits.start', $visit))
            ->assertSessionHasNoErrors();

        $this->post(route('visits.examination', $visit), [
            'diagnosis' => 'Catatan pemeriksaan simulasi',
            'notes' => 'Data fiktif untuk pengujian alur presentasi.',
            'medication_id' => $medication->id,
            'dosage' => 'Sesuai instruksi simulasi',
            'quantity' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($pharmacist)
            ->post(route('visits.dispense', $visit))
            ->assertSessionHasNoErrors();

        $this->actingAs($cashier)
            ->post(route('visits.payment', $visit), [
                'payment_method' => 'cash',
                'amount_received' => $visit->invoice->amount,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('prescriptions', [
            'visit_id' => $visit->id,
            'status' => 'dispensed',
            'dispensed_by' => $pharmacist->id,
        ]);
        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'status' => 'paid',
            'paid_by' => $cashier->id,
        ]);
    }

    public function test_hospital_seeder_refuses_to_run_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data rumah sakit fiktif hanya boleh dibuat pada environment local atau testing.');

        app(HospitalDemoSeeder::class)->run();
    }

    public function test_workflow_seeder_refuses_to_run_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Akun presentasi hanya boleh dibuat pada environment local atau testing.');

        app(WorkflowDemoSeeder::class)->run();
    }
}
