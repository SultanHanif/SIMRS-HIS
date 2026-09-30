<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_can_print_an_antrean_ticket_from_visit_details(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create([
            'name' => 'Siti Aminah',
            'medical_record_number' => 'RM-TICKET-001',
        ]);
        $clinic = Clinic::factory()->create(['name' => 'Poli Umum']);
        $doctor = Doctor::factory()->for($clinic)->create(['name' => 'dr. Budi']);
        $visit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'visit_number' => 'RJ-TEST-2026',
            'visited_at' => '2026-09-30 11:00:00',
            'queue_date' => '2026-09-30',
            'queue_number' => 7,
        ]);

        $this->actingAs($registrar)
            ->get(route('visits.show', $visit))
            ->assertSee(route('visits.ticket', $visit), false)
            ->assertSee('Cetak tiket antrean')
            ->assertSee('Nomor antrean')
            ->assertSee('007');

        $this->get(route('visits.ticket', $visit))
            ->assertOk()
            ->assertSee('Nomor antrean ·')
            ->assertSee('007')
            ->assertSee('No. kunjungan: RJ-TEST-2026')
            ->assertSee('Siti Aminah')
            ->assertSee('RM-TICKET-001')
            ->assertSee('Poli Umum')
            ->assertSee('dr. Budi')
            ->assertSee('Cetak tiket')
            ->assertSee('window.print()', false);
    }

    public function test_ticket_escapes_patient_and_clinic_names_in_the_printed_page(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $patient = Patient::factory()->create(['name' => '<script>alert(1)</script>']);
        $clinic = Clinic::factory()->create(['name' => 'Poli <script>']);
        $doctor = Doctor::factory()->for($clinic)->create();
        $visit = Visit::factory()->for($patient)->for($clinic)->for($doctor)->create();

        $this->actingAs($registrar)
            ->get(route('visits.ticket', $visit))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('Poli &lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_ticket_printing_requires_front_office_access(): void
    {
        $visit = Visit::factory()->create();

        $this->get(route('visits.ticket', $visit))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'farmasi']))
            ->get(route('visits.ticket', $visit))
            ->assertForbidden();
    }
}
