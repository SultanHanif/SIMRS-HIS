<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_can_view_todays_queue_for_a_selected_clinic(): void
    {
        $this->freezeTime();
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create();
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => now()->subMinutes(30),
            'visit_number' => 'RJ-EARLY-001',
            'queue_date' => today()->toDateString(),
            'queue_number' => 1,
        ]);
        $priorityVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => now()->subMinutes(10),
            'queue_priority' => 'priority',
            'visit_number' => 'RJ-PRIORITY-001',
            'queue_date' => today()->toDateString(),
            'queue_number' => 2,
        ]);
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => now()->subDay(),
            'visit_number' => 'RJ-YESTERDAY-001',
        ]);

        $this->actingAs($registrar)
            ->get(route('queue.index', ['clinic_id' => $clinic->id]))
            ->assertOk()
            ->assertSee('RJ-PRIORITY-001')
            ->assertSee('RJ-EARLY-001')
            ->assertSee('002')
            ->assertSee('001')
            ->assertDontSee('RJ-YESTERDAY-001')
            ->assertSeeInOrder(['RJ-PRIORITY-001', 'RJ-EARLY-001']);

    }

    public function test_front_office_can_call_the_next_unannounced_patient_by_priority(): void
    {
        $this->freezeTime();
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create();
        $normalVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => now()->subMinutes(20),
            'visit_number' => 'RJ-NORMAL-001',
            'queue_date' => today()->toDateString(),
            'queue_number' => 1,
        ]);
        $priorityVisit = Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => now()->subMinutes(5),
            'queue_priority' => 'priority',
            'visit_number' => 'RJ-PRIORITY-001',
            'queue_date' => today()->toDateString(),
            'queue_number' => 2,
        ]);

        $this->actingAs($registrar)
            ->post(route('queue.call-next'), ['clinic_id' => $clinic->id])
            ->assertRedirect(route('queue.index', ['clinic_id' => $clinic->id]))
            ->assertSessionHas('success', 'Antrean 002 atas nama '.$priorityVisit->patient->name.' dipanggil.');

        $this->assertDatabaseHas('visits', [
            'id' => $priorityVisit->id,
            'status' => 'waiting',
            'called_at' => now()->toDateTimeString(),
            'called_by' => $registrar->id,
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $normalVisit->id,
            'called_at' => null,
        ]);

        $this->post(route('queue.call-next'), ['clinic_id' => $clinic->id])
            ->assertRedirect(route('queue.index', ['clinic_id' => $clinic->id]))
            ->assertSessionHas('success', 'Antrean 001 atas nama '.$normalVisit->patient->name.' dipanggil.');
    }

    public function test_front_office_can_change_priority_only_while_visit_is_waiting(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $visit = Visit::factory()->create();

        $this->actingAs($registrar)
            ->put(route('queue.priority', $visit), [
                'queue_priority' => 'priority',
                'status' => 'completed',
            ])
            ->assertRedirect(route('queue.index', ['clinic_id' => $visit->clinic_id]));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'queue_priority' => 'priority',
            'status' => 'waiting',
        ]);

        $visit->update(['status' => 'in_consultation']);

        $this->put(route('queue.priority', $visit), ['queue_priority' => 'normal'])
            ->assertConflict();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'queue_priority' => 'priority',
        ]);
    }

    public function test_invalid_queue_priority_is_rejected(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $visit = Visit::factory()->create();

        $this->actingAs($registrar)
            ->put(route('queue.priority', $visit), ['queue_priority' => 'emergency'])
            ->assertSessionHasErrors([
                'queue_priority' => 'The selected queue priority is invalid.',
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'queue_priority' => 'normal',
        ]);
    }

    public function test_front_office_can_mark_a_waiting_visit_as_no_show(): void
    {
        $this->freezeTime();
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $visit = Visit::factory()->create();

        $this->actingAs($registrar)
            ->post(route('queue.no-show', $visit))
            ->assertRedirect(route('queue.index', ['clinic_id' => $visit->clinic_id]));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'no_show',
            'no_show_at' => now()->toDateTimeString(),
            'no_show_by' => $registrar->id,
        ]);
    }

    public function test_front_office_can_cancel_a_waiting_visit(): void
    {
        $this->freezeTime();
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $visit = Visit::factory()->create();

        $this->actingAs($registrar)
            ->delete(route('queue.cancel', $visit))
            ->assertRedirect(route('queue.index', ['clinic_id' => $visit->clinic_id]));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'cancelled',
            'cancelled_at' => now()->toDateTimeString(),
            'cancelled_by' => $registrar->id,
        ]);
    }

    public function test_call_next_warns_when_every_waiting_patient_has_already_been_called(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create();
        $doctor = Doctor::factory()->for($clinic)->create();
        $visit = Visit::factory()->for($clinic)->for($doctor)->create([
            'called_at' => now(),
            'called_by' => $registrar->id,
        ]);

        $this->actingAs($registrar)
            ->post(route('queue.call-next'), ['clinic_id' => $clinic->id])
            ->assertRedirect(route('queue.index', ['clinic_id' => $clinic->id]))
            ->assertSessionHas('warning', 'Tidak ada pasien menunggu yang belum dipanggil di poli ini.');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'waiting',
            'called_by' => $registrar->id,
        ]);
    }

    public function test_queue_actions_cannot_close_a_visit_after_examination_has_started(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $visit = Visit::factory()->create(['status' => 'in_consultation']);

        $this->actingAs($registrar)
            ->post(route('queue.no-show', $visit))
            ->assertConflict();

        $this->delete(route('queue.cancel', $visit))
            ->assertConflict();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'in_consultation',
            'no_show_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function test_queue_management_requires_front_office_access(): void
    {
        $visit = Visit::factory()->create();

        $this->get(route('queue.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('queue.index'))
            ->assertForbidden();

        $this->post(route('queue.no-show', $visit))->assertForbidden();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'waiting',
        ]);
    }
}
