<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_print_a_paid_receipt_without_clinical_details(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir', 'name' => 'Petugas Kasir']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create(['name' => 'dr. Contoh']);
        $patient = Patient::factory()->create([
            'name' => 'Pasien <Bukti>',
            'medical_record_number' => 'RM-KUITANSI-001',
        ]);
        $visit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visit_number' => 'RJ-KUITANSI-001',
            'payment_method' => 'general',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $visit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis privat pasien',
            'notes' => 'Catatan klinis privat pasien',
        ]);
        Invoice::query()->create([
            'visit_id' => $visit->id,
            'amount' => 125000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-09-30 14:00:00',
        ]);

        $this->actingAs($cashier)
            ->get(route('visits.receipt', $visit))
            ->assertSee('Kuitansi pembayaran')
            ->assertSee('INV-000001')
            ->assertSee('Pasien &lt;Bukti&gt;', false)
            ->assertSee('RM-KUITANSI-001')
            ->assertSee('Rp 125.000')
            ->assertSee('Petugas Kasir')
            ->assertSee('Cetak kuitansi')
            ->assertDontSee('Diagnosis privat pasien')
            ->assertDontSee('Catatan klinis privat pasien')
            ->assertDontSee('<script', false);
    }

    public function test_unpaid_invoice_does_not_provide_a_receipt(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $visit = Visit::factory()->create(['status' => 'awaiting_payment']);
        Invoice::query()->create([
            'visit_id' => $visit->id,
            'amount' => 75000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($cashier)
            ->get(route('visits.receipt', $visit))
            ->assertConflict();
    }

    public function test_only_cashier_or_super_admin_can_open_the_receipt(): void
    {
        $visit = Visit::factory()->create(['status' => 'completed']);
        Invoice::query()->create([
            'visit_id' => $visit->id,
            'amount' => 75000,
            'status' => 'paid',
            'paid_at' => '2026-09-30 14:00:00',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('visits.receipt', $visit))
            ->assertForbidden();
    }
}
