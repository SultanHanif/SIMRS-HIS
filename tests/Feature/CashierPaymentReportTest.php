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

class CashierPaymentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_filter_payment_report_by_date_and_search_receipts(): void
    {
        $this->travelTo('2026-09-30 14:00:00');
        $cashier = User::factory()->create(['role' => 'kasir', 'name' => 'Kasir Satu']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create();

        $patient = Patient::factory()->create([
            'name' => 'Pasien Pertama <script>alert("xss")</script>',
            'medical_record_number' => 'RM-PAY-001',
        ]);
        $firstVisit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visit_number' => 'RJ-PAY-001',
            'status' => 'completed',
        ]);
        Examination::query()->create([
            'visit_id' => $firstVisit->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Diagnosis yang tidak boleh ditampilkan',
            'notes' => 'Catatan klinis privat',
        ]);
        $firstInvoice = Invoice::query()->create([
            'visit_id' => $firstVisit->id,
            'amount' => 75000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-09-29 11:30:00',
        ]);

        $secondPatient = Patient::factory()->create([
            'name' => 'Pasien Kedua',
            'medical_record_number' => 'RM-PAY-002',
        ]);
        $secondVisit = Visit::factory()->for($secondPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visit_number' => 'RJ-PAY-002',
            'status' => 'completed',
        ]);
        $secondInvoice = Invoice::query()->create([
            'visit_id' => $secondVisit->id,
            'amount' => 125000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-09-30 12:30:00',
        ]);

        $outsidePeriodPatient = Patient::factory()->create(['name' => 'Pasien di luar periode']);
        $outsidePeriodVisit = Visit::factory()->for($outsidePeriodPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'completed',
        ]);
        Invoice::query()->create([
            'visit_id' => $outsidePeriodVisit->id,
            'amount' => 50000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-10-01 08:00:00',
        ]);

        $unpaidVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'awaiting_payment',
        ]);
        Invoice::query()->create([
            'visit_id' => $unpaidVisit->id,
            'amount' => 100000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($cashier)
            ->get(route('reports.payments', [
                'date_from' => '2026-09-29',
                'date_to' => '2026-09-30',
            ]))
            ->assertViewIs('reports.payments')
            ->assertViewHas('totalTransactions', 2)
            ->assertViewHas('totalAmount', 200000)
            ->assertSee('Pasien Pertama')
            ->assertSee('Pasien Pertama &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('Pasien Kedua')
            ->assertSee('INV-'.str_pad((string) $firstInvoice->id, 6, '0', STR_PAD_LEFT))
            ->assertDontSee('Diagnosis yang tidak boleh ditampilkan')
            ->assertDontSee('Catatan klinis privat')
            ->assertDontSee('Pasien di luar periode');

        $this->get(route('reports.payments', ['search' => 'RM-PAY-002']))
            ->assertSee('Pasien Kedua')
            ->assertDontSee('Pasien Pertama')
            ->assertSee('125.000');

        $this->get(route('reports.payments', [
            'date_from' => '2026-09-29',
            'date_to' => '2026-09-30',
            'search' => 'RJ-PAY-001',
        ]))
            ->assertSee('Pasien Pertama')
            ->assertDontSee('Pasien Kedua');

        $this->get(route('reports.payments', [
            'date_from' => '2026-09-29',
            'date_to' => '2026-09-30',
            'search' => 'Kasir Satu',
        ]))
            ->assertSee('Pasien Pertama')
            ->assertSee('Pasien Kedua');

        $this->get(route('reports.payments', ['search' => 'INV-'.str_pad((string) $secondInvoice->id, 6, '0', STR_PAD_LEFT)]))
            ->assertSee('Pasien Kedua')
            ->assertDontSee('Pasien Pertama');

        $this->get(route('reports.payments', ['search' => '\'% OR 1=1 --']))
            ->assertSee('Pembayaran tidak ditemukan')
            ->assertDontSee('Pasien Pertama')
            ->assertDontSee('Pasien Kedua');
    }

    public function test_payment_report_rejects_invalid_date_ranges_and_denies_unprivileged_roles(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($cashier)
            ->get(route('reports.payments', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-09-30',
            ]))
            ->assertSessionHasErrors('date_to');

        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('reports.payments'))
            ->assertForbidden();
    }
}
