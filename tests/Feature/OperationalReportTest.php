<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_can_view_operational_totals_by_clinic_and_status_without_patient_details(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-UMUM',
            'name' => 'Poli Umum',
        ]);
        $otherClinic = Clinic::factory()->create([
            'code' => 'POL-ANAK',
            'name' => 'Poli Anak',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create();
        $otherDoctor = Doctor::factory()->for($otherClinic)->create();

        foreach ([
            [$clinic, $doctor, 'waiting', null, 'Rahasia Pasien Menunggu'],
            [$clinic, $doctor, 'waiting', now()->subMinutes(5), 'Rahasia Pasien Dipanggil'],
            [$clinic, $doctor, 'in_consultation', null, 'Rahasia Pasien Diperiksa'],
            [$clinic, $doctor, 'awaiting_pharmacy', null, 'Rahasia Pasien Farmasi'],
            [$clinic, $doctor, 'awaiting_payment', null, 'Rahasia Pasien Kasir'],
            [$clinic, $doctor, 'completed', null, 'Rahasia Pasien Selesai'],
            [$clinic, $doctor, 'no_show', null, 'Rahasia Pasien Absen'],
            [$clinic, $doctor, 'cancelled', null, 'Rahasia Pasien Batal'],
            [$otherClinic, $otherDoctor, 'waiting', null, 'Rahasia Pasien Poli Lain'],
        ] as [$visitClinic, $visitDoctor, $status, $calledAt, $patientName]) {
            $patient = Patient::factory()->create(['name' => $patientName]);
            Visit::factory()->for($patient)->for($visitClinic)->for($visitDoctor)->create([
                'registered_by' => $registrar->id,
                'visited_at' => now()->subMinutes(20),
                'status' => $status,
                'called_at' => $calledAt,
                'complaint' => 'RAHASIA KLINIS',
            ]);
        }

        Visit::factory()->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        $this->actingAs($registrar)
            ->get(route('reports.operational', ['date' => '2026-09-30']))
            ->assertSee('Laporan operasional')
            ->assertSee('Poli Umum')
            ->assertSee('Poli Anak')
            ->assertSee('Tidak hadir')
            ->assertViewHas('totals', [
                'visits' => 9,
                'waiting' => 3,
                'in_service' => 3,
                'completed' => 1,
                'no_show' => 1,
                'cancelled' => 1,
            ])
            ->assertViewHas('clinics', function ($clinics) use ($clinic, $otherClinic): bool {
                return $clinics->firstWhere('id', $clinic->id) === [
                    'id' => $clinic->id,
                    'code' => 'POL-UMUM',
                    'name' => 'Poli Umum',
                    'total' => 8,
                    'waiting' => 2,
                    'called' => 1,
                    'uncalled' => 1,
                    'in_service' => 3,
                    'completed' => 1,
                    'no_show' => 1,
                    'cancelled' => 1,
                ] && $clinics->firstWhere('id', $otherClinic->id)['total'] === 1;
            })
            ->assertDontSee('Rahasia Pasien')
            ->assertDontSee('RAHASIA KLINIS');
    }

    public function test_operational_report_can_be_filtered_to_one_clinic(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'super_admin']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Terpilih']);
        $otherClinic = Clinic::factory()->create(['name' => 'Poli Lain']);
        $doctor = Doctor::factory()->for($clinic)->create();
        $otherDoctor = Doctor::factory()->for($otherClinic)->create();

        Visit::factory()->for($clinic)->for($doctor)->create(['visited_at' => now()]);
        Visit::factory()->for($otherClinic)->for($otherDoctor)->create(['visited_at' => now()]);

        $this->actingAs($registrar)
            ->get(route('reports.operational', [
                'date' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->assertSee('Poli Terpilih')
            ->assertSee('Cetak laporan')
            ->assertSee('window.print()', false)
            ->assertSee(route('reports.operational.export', [
                'date_from' => '2026-09-30',
                'date_to' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->assertViewHas('clinics', fn ($clinics): bool => $clinics->count() === 1
                && $clinics->first()['name'] === 'Poli Terpilih'
                && $clinics->first()['total'] === 1);
    }

    public function test_operational_report_rejects_invalid_date_and_unknown_clinic(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->get(route('reports.operational', [
                'date' => 'not-a-date',
                'clinic_id' => 99999,
            ]))
            ->assertSessionHasErrors(['date', 'clinic_id']);
    }

    public function test_operational_report_can_be_filtered_to_a_date_range(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Rentang']);
        $doctor = Doctor::factory()->for($clinic)->create();

        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-09-28 09:00:00',
            'status' => 'completed',
        ]);
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-09-30 09:00:00',
            'status' => 'waiting',
        ]);
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-10-01 09:00:00',
            'status' => 'completed',
        ]);

        $this->actingAs($registrar)
            ->get(route('reports.operational', [
                'date_from' => '2026-09-28',
                'date_to' => '2026-09-30',
            ]))
            ->assertSee('28 September 2026 s.d. 30 September 2026')
            ->assertViewHas('totals', [
                'visits' => 2,
                'waiting' => 1,
                'in_service' => 0,
                'completed' => 1,
                'no_show' => 0,
                'cancelled' => 0,
            ])
            ->assertViewHas('clinics', fn ($clinics): bool => $clinics->first()['total'] === 2);
    }

    public function test_operational_report_offers_date_presets_that_keep_the_selected_clinic(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create(['name' => 'Poli Pilihan']);

        $this->actingAs($registrar)
            ->get(route('reports.operational', [
                'date_from' => '2026-09-28',
                'date_to' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->assertSee('7 hari terakhir')
            ->assertSee('Bulan ini')
            ->assertSee('Bulan lalu')
            ->assertSee(route('reports.operational', [
                'date_from' => '2026-09-24',
                'date_to' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->assertSee(route('reports.operational', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->assertSee(route('reports.operational', [
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
                'clinic_id' => $clinic->id,
            ]));
    }

    public function test_active_quick_period_is_visually_and_accessibly_marked(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $response = $this->actingAs($registrar)
            ->get(route('reports.operational', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]));

        $response->assertSee('Bulan ini');
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="date"'));
    }

    public function test_operational_report_rejects_incomplete_or_reversed_date_ranges(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->get(route('reports.operational', ['date_from' => '2026-09-30']))
            ->assertSessionHasErrors('date_to');

        $this->get(route('reports.operational', [
            'date_from' => '2026-09-30',
            'date_to' => '2026-09-29',
        ]))
            ->assertSessionHasErrors('date_to');
    }

    public function test_operational_report_requires_front_office_access(): void
    {
        $this->get(route('reports.operational'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('reports.operational'))
            ->assertForbidden();
    }

    public function test_front_office_can_download_an_aggregated_csv_without_patient_details(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-UMUM',
            'name' => 'Poli Umum',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create();
        $patient = Patient::factory()->create(['name' => 'Rahasia Pasien CSV']);
        Visit::factory()->for($patient)->for($clinic)->for($doctor)->create([
            'registered_by' => $registrar->id,
            'visited_at' => now(),
            'status' => 'waiting',
            'complaint' => 'RAHASIA KLINIS CSV',
        ]);

        $response = $this->actingAs($registrar)
            ->get(route('reports.operational.export', ['date' => '2026-09-30']));

        $response->assertStreamed()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString(
            'filename=laporan-operasional-2026-09-30.csv',
            (string) $response->headers->get('content-disposition'),
        );

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringNotContainsString('Rahasia Pasien CSV', $csv);
        $this->assertStringNotContainsString('RAHASIA KLINIS CSV', $csv);

        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            array_filter(explode("\r\n", substr($csv, 3))),
        );

        $this->assertSame([
            ['Periode', 'Kode poli', 'Poli', 'Total kunjungan', 'Menunggu', 'Sudah dipanggil', 'Belum dipanggil', 'Dalam layanan', 'Selesai', 'Tidak hadir', 'Dibatalkan'],
            ['2026-09-30', 'POL-UMUM', 'Poli Umum', '1', '1', '0', '1', '0', '0', '0', '0'],
            ['2026-09-30', '', 'Total', '1', '1', '0', '1', '0', '0', '0', '0'],
        ], $rows);
    }

    public function test_operational_csv_export_uses_the_selected_date_and_clinic_filters(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'super_admin']);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-PILIHAN',
            'name' => 'Poli Pilihan',
        ]);
        $otherClinic = Clinic::factory()->create([
            'code' => 'POL-LAIN',
            'name' => 'Poli Lain',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create();
        $otherDoctor = Doctor::factory()->for($otherClinic)->create();

        Visit::factory()->for($clinic)->for($doctor)->create(['visited_at' => now()]);
        Visit::factory()->for($clinic)->for($doctor)->create(['visited_at' => now()->subDay()]);
        Visit::factory()->for($otherClinic)->for($otherDoctor)->create(['visited_at' => now()]);

        $csv = $this->actingAs($registrar)
            ->get(route('reports.operational.export', [
                'date' => '2026-09-30',
                'clinic_id' => $clinic->id,
            ]))
            ->streamedContent();

        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            array_filter(explode("\r\n", substr($csv, 3))),
        );

        $this->assertSame(
            ['2026-09-30', 'POL-PILIHAN', 'Poli Pilihan', '1', '1', '0', '1', '0', '0', '0', '0'],
            $rows[1],
        );
        $this->assertStringNotContainsString('POL-LAIN', $csv);
        $this->assertStringNotContainsString('Poli Lain', $csv);
    }

    public function test_operational_csv_export_includes_the_selected_date_range(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'super_admin']);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-RENTANG',
            'name' => 'Poli Rentang',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create();

        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-09-28 09:00:00',
            'status' => 'completed',
        ]);
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-09-30 09:00:00',
            'status' => 'waiting',
        ]);
        Visit::factory()->for($clinic)->for($doctor)->create([
            'visited_at' => '2026-10-01 09:00:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($registrar)
            ->get(route('reports.operational.export', [
                'date_from' => '2026-09-28',
                'date_to' => '2026-09-30',
            ]));

        $response->assertHeader(
            'content-disposition',
            'attachment; filename=laporan-operasional-2026-09-28-sampai-2026-09-30.csv',
        );

        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            array_filter(explode("\r\n", substr($response->streamedContent(), 3))),
        );

        $this->assertSame(
            ['Periode', 'Kode poli', 'Poli', 'Total kunjungan', 'Menunggu', 'Sudah dipanggil', 'Belum dipanggil', 'Dalam layanan', 'Selesai', 'Tidak hadir', 'Dibatalkan'],
            $rows[0],
        );
        $this->assertSame(
            ['2026-09-28 s.d. 2026-09-30', 'POL-RENTANG', 'Poli Rentang', '2', '1', '0', '1', '0', '1', '0', '0'],
            $rows[1],
        );
        $this->assertSame('2', $rows[2][3]);
    }

    public function test_operational_csv_export_escapes_spreadsheet_formulas_in_clinic_names(): void
    {
        $this->travelTo('2026-09-30 11:00:00');
        $registrar = User::factory()->create(['role' => 'administrasi']);
        $clinic = Clinic::factory()->create([
            'code' => 'POL-UMUM',
            'name' => '=HYPERLINK("https://example.test","Klik")',
        ]);
        $doctor = Doctor::factory()->for($clinic)->create();
        Visit::factory()->for($clinic)->for($doctor)->create(['visited_at' => now()]);

        $csv = $this->actingAs($registrar)
            ->get(route('reports.operational.export', ['date' => '2026-09-30']))
            ->streamedContent();
        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            array_filter(explode("\r\n", substr($csv, 3))),
        );

        $this->assertSame("'=HYPERLINK(\"https://example.test\",\"Klik\")", $rows[1][2]);
    }

    public function test_operational_csv_export_rejects_invalid_filters(): void
    {
        $registrar = User::factory()->create(['role' => 'administrasi']);

        $this->actingAs($registrar)
            ->get(route('reports.operational.export', [
                'date' => 'not-a-date',
                'clinic_id' => 99999,
            ]))
            ->assertSessionHasErrors(['date', 'clinic_id']);
    }

    public function test_operational_csv_export_requires_front_office_access(): void
    {
        $this->get(route('reports.operational.export'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('reports.operational.export'))
            ->assertForbidden();
    }
}
