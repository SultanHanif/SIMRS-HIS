<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontOfficeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_dashboard_summarizes_today_by_status_and_clinic(): void
    {
        $this->travelTo('2026-09-30 10:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create(['name' => 'dr. Dashboard']);

        foreach ([
            ['Pasien Prioritas', 'waiting', 'priority', now()->setTime(9, 55), null],
            ['Pasien Normal', 'waiting', 'normal', now()->setTime(9, 0), null],
            ['Pasien Dipanggil', 'waiting', 'normal', now()->setTime(9, 30), now()->setTime(9, 45)],
            ['Pasien Diperiksa', 'in_consultation', 'normal', now()->setTime(9, 35), null],
            ['Pasien Farmasi', 'awaiting_pharmacy', 'normal', now()->setTime(9, 40), null],
            ['Pasien Kasir', 'awaiting_payment', 'normal', now()->setTime(9, 45), null],
            ['Pasien Selesai', 'completed', 'normal', now()->setTime(9, 50), null],
            ['Pasien Tidak Hadir', 'no_show', 'normal', now()->setTime(9, 51), null],
            ['Pasien Dibatalkan', 'cancelled', 'normal', now()->setTime(9, 52), null],
        ] as [$name, $status, $priority, $visitedAt, $calledAt]) {
            $patient = Patient::factory()->create(['name' => $name]);

            Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
                'registered_by' => $registrar->id,
                'visited_at' => $visitedAt,
                'status' => $status,
                'queue_priority' => $priority,
                'called_at' => $calledAt,
            ]);
        }

        $yesterdayPatient = Patient::factory()->create(['name' => 'Pasien Kemarin']);
        Visit::factory()->for($yesterdayPatient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->subDay(),
            'status' => 'waiting',
        ]);

        $this->actingAs($registrar)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.front-office')
            ->assertViewHas('visitsToday', 9)
            ->assertViewHas('waitingCount', 3)
            ->assertViewHas('calledCount', 1)
            ->assertViewHas('uncalledCount', 2)
            ->assertViewHas('activeCount', 3)
            ->assertViewHas('completedCount', 1)
            ->assertViewHas('clinicSummaries', function ($summaries) use ($clinic): bool {
                $clinicSummary = $summaries->firstWhere('id', $clinic->id);

                return $clinicSummary === [
                    'id' => $clinic->id,
                    'name' => 'Poli Umum',
                    'waiting' => 3,
                    'in_consultation' => 1,
                    'awaiting_pharmacy' => 1,
                    'awaiting_payment' => 1,
                    'completed' => 1,
                    'no_show' => 1,
                    'cancelled' => 1,
                ];
            })
            ->assertSeeInOrder(['Pasien Prioritas', 'Pasien Normal', 'Pasien Dipanggil'])
            ->assertSee('1 menunggu farmasi')
            ->assertSee('1 menunggu kasir')
            ->assertDontSee('Pasien Kemarin')
            ->assertSee(route('queue.index'), false)
            ->assertSee(route('visits.create'), false);
    }

    public function test_super_admin_receives_the_front_office_dashboard(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.front-office')
            ->assertSee('Workspace Admin / Front Office')
            ->assertSee('Ringkasan poli hari ini');
    }

    public function test_cashier_sees_a_dedicated_dashboard_instead_of_the_front_office_dashboard(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertViewIs('dashboard.cashier')
            ->assertSee('Antrean tagihan')
            ->assertSee('Pembayaran hari ini')
            ->assertDontSee('Ringkasan poli hari ini');
    }
}
