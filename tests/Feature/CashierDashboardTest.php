<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_dashboard_summarizes_unpaid_and_today_paid_invoices(): void
    {
        $this->travelTo('2026-09-30 14:00:00');
        $cashier = User::factory()->create(['role' => 'kasir']);
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create();

        $firstPatient = Patient::factory()->create(['name' => 'Pasien Tagihan Lama']);
        $firstVisit = Visit::factory()->for($firstPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visit_number' => 'RJ-KASIR-001',
            'visited_at' => '2026-09-30 09:00:00',
            'status' => 'awaiting_payment',
        ]);
        Invoice::query()->create([
            'visit_id' => $firstVisit->id,
            'amount' => 75000,
            'status' => 'unpaid',
        ]);

        $secondPatient = Patient::factory()->create(['name' => 'Pasien Tagihan Baru']);
        $secondVisit = Visit::factory()->for($secondPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visit_number' => 'RJ-KASIR-002',
            'visited_at' => '2026-09-30 10:00:00',
            'status' => 'awaiting_payment',
        ]);
        Invoice::query()->create([
            'visit_id' => $secondVisit->id,
            'amount' => 125000,
            'status' => 'unpaid',
        ]);

        $paidVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'status' => 'completed',
        ]);
        Invoice::query()->create([
            'visit_id' => $paidVisit->id,
            'amount' => 50000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-09-30 13:00:00',
        ]);

        $yesterdayVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => '2026-09-29 09:00:00',
            'status' => 'completed',
        ]);
        Invoice::query()->create([
            'visit_id' => $yesterdayVisit->id,
            'amount' => 90000,
            'status' => 'paid',
            'paid_by' => $cashier->id,
            'paid_at' => '2026-09-29 13:00:00',
        ]);

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.cashier')
            ->assertViewHas('pendingCount', 2)
            ->assertViewHas('pendingAmount', 200000)
            ->assertViewHas('paidTodayCount', 1)
            ->assertViewHas('paidTodayAmount', 50000)
            ->assertViewHas('pendingVisits', fn ($visits): bool => $visits->modelKeys() === [$firstVisit->id, $secondVisit->id])
            ->assertSeeInOrder(['Pasien Tagihan Lama', 'RJ-KASIR-001', 'Pasien Tagihan Baru', 'RJ-KASIR-002'])
            ->assertSee('Pembayaran terbaru hari ini')
            ->assertDontSee('Pasien Tagihan Kemarin');
    }
}
