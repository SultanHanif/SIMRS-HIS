<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_cash_payment_and_change(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $visit = Visit::factory()->create(['status' => 'awaiting_payment']);
        $invoice = Invoice::query()->create([
            'visit_id' => $visit->id,
            'amount' => 75000,
        ]);

        $this->actingAs($cashier)
            ->post(route('visits.payment', $visit), [
                'payment_method' => 'cash',
                'amount_received' => 100000,
                'payment_notes' => 'Uang diterima di loket utama.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'payment_method' => 'cash',
            'amount_received' => 100000,
            'change_amount' => 25000,
            'payment_notes' => 'Uang diterima di loket utama.',
            'paid_by' => $cashier->id,
        ]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'completed']);
        $this->get(route('visits.receipt', $visit))
            ->assertSee('Tunai')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 25.000');
    }

    public function test_non_cash_payment_requires_reference_and_exact_amount(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $visit = Visit::factory()->create(['status' => 'awaiting_payment']);
        Invoice::query()->create(['visit_id' => $visit->id, 'amount' => 75000]);

        $this->actingAs($cashier)
            ->post(route('visits.payment', $visit), [
                'payment_method' => 'qris',
                'amount_received' => 80000,
            ])
            ->assertSessionHasErrors(['amount_received', 'payment_reference']);

        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'status' => 'unpaid',
        ]);
    }

    public function test_cashier_records_non_cash_payment_reference(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $visit = Visit::factory()->create(['status' => 'awaiting_payment']);
        $invoice = Invoice::query()->create(['visit_id' => $visit->id, 'amount' => 75000]);

        $this->actingAs($cashier)
            ->post(route('visits.payment', $visit), [
                'payment_method' => 'qris',
                'amount_received' => 75000,
                'payment_reference' => 'QRIS-20260930-001',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'payment_method' => 'qris',
            'amount_received' => 75000,
            'change_amount' => 0,
            'payment_reference' => 'QRIS-20260930-001',
        ]);
        $this->get(route('visits.receipt', $visit))
            ->assertSee('QRIS')
            ->assertSee('QRIS-20260930-001');
    }
}
