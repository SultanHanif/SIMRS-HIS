<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        ['start_date' => $reportStartDate, 'end_date' => $reportEndDate, 'clinic_id' => $clinicId] = $this->reportFilters($request);
        $clinics = $this->clinicRows($reportStartDate, $reportEndDate, $clinicId);

        $totals = [
            'visits' => (int) $clinics->sum('total'),
            'waiting' => (int) $clinics->sum('waiting'),
            'in_service' => (int) $clinics->sum('in_service'),
            'completed' => (int) $clinics->sum('completed'),
            'no_show' => (int) $clinics->sum('no_show'),
            'cancelled' => (int) $clinics->sum('cancelled'),
        ];

        $availableClinics = Clinic::query()->orderBy('name')->get(['id', 'name']);

        return view('reports.operational', compact(
            'availableClinics',
            'clinicId',
            'clinics',
            'reportEndDate',
            'reportStartDate',
            'totals',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        ['start_date' => $reportStartDate, 'end_date' => $reportEndDate, 'clinic_id' => $clinicId] = $this->reportFilters($request);
        $clinics = $this->clinicRows($reportStartDate, $reportEndDate, $clinicId);
        $periodLabel = $this->periodLabel($reportStartDate, $reportEndDate);
        $filename = $reportStartDate === $reportEndDate
            ? "laporan-operasional-{$reportStartDate}.csv"
            : "laporan-operasional-{$reportStartDate}-sampai-{$reportEndDate}.csv";

        return response()->streamDownload(function () use ($clinics, $periodLabel): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka stream laporan CSV.');
            }

            try {
                if (fwrite($output, "\xEF\xBB\xBF") !== 3) {
                    throw new RuntimeException('Tidak dapat menulis encoding laporan CSV.');
                }

                foreach ($this->csvRows($clinics, $periodLabel) as $row) {
                    $this->writeCsvRow($output, $row);
                }
            } finally {
                fclose($output);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{start_date: string, end_date: string, clinic_id: int|null}
     */
    private function reportFilters(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'required_with:date_to', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_with:date_from', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
        ], [
            'date_from.required_with' => 'Tanggal awal wajib dipilih jika tanggal akhir diisi.',
            'date_to.required_with' => 'Tanggal akhir wajib dipilih jika tanggal awal diisi.',
            'date_to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        $fallbackDate = (string) ($validated['date'] ?? today()->toDateString());

        return [
            'start_date' => (string) ($validated['date_from'] ?? $validated['date'] ?? $validated['date_to'] ?? $fallbackDate),
            'end_date' => (string) ($validated['date_to'] ?? $validated['date'] ?? $validated['date_from'] ?? $fallbackDate),
            'clinic_id' => isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null,
        ];
    }

    /**
     * @return Collection<int, array{id: int, code: string, name: string, total: int, waiting: int, called: int, uncalled: int, in_service: int, completed: int, no_show: int, cancelled: int}>
     */
    private function clinicRows(string $reportStartDate, string $reportEndDate, ?int $clinicId): Collection
    {
        $countsByClinic = Visit::query()
            ->whereDate('visited_at', '>=', $reportStartDate)
            ->whereDate('visited_at', '<=', $reportEndDate)
            ->when($clinicId !== null, fn (Builder $query) => $query->where('clinic_id', $clinicId))
            ->select('clinic_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting")
            ->selectRaw("SUM(CASE WHEN status = 'waiting' AND called_at IS NOT NULL THEN 1 ELSE 0 END) as called")
            ->selectRaw("SUM(CASE WHEN status = 'waiting' AND called_at IS NULL THEN 1 ELSE 0 END) as uncalled")
            ->selectRaw("SUM(CASE WHEN status IN ('in_consultation', 'awaiting_pharmacy', 'awaiting_payment') THEN 1 ELSE 0 END) as in_service")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->groupBy('clinic_id')
            ->get()
            ->keyBy('clinic_id');

        return Clinic::query()
            ->when($clinicId !== null, fn (Builder $query) => $query->whereKey($clinicId))
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(function (Clinic $clinic) use ($countsByClinic): array {
                $counts = $countsByClinic->get($clinic->id);

                return [
                    'id' => $clinic->id,
                    'code' => $clinic->code,
                    'name' => $clinic->name,
                    'total' => (int) ($counts->total ?? 0),
                    'waiting' => (int) ($counts->waiting ?? 0),
                    'called' => (int) ($counts->called ?? 0),
                    'uncalled' => (int) ($counts->uncalled ?? 0),
                    'in_service' => (int) ($counts->in_service ?? 0),
                    'completed' => (int) ($counts->completed ?? 0),
                    'no_show' => (int) ($counts->no_show ?? 0),
                    'cancelled' => (int) ($counts->cancelled ?? 0),
                ];
            });
    }

    /**
     * @param  Collection<int, array{id: int, code: string, name: string, total: int, waiting: int, called: int, uncalled: int, in_service: int, completed: int, no_show: int, cancelled: int}>  $clinics
     * @return iterable<array<int, int|string>>
     */
    private function csvRows(Collection $clinics, string $periodLabel): iterable
    {
        yield [
            'Periode',
            'Kode poli',
            'Poli',
            'Total kunjungan',
            'Menunggu',
            'Sudah dipanggil',
            'Belum dipanggil',
            'Dalam layanan',
            'Selesai',
            'Tidak hadir',
            'Dibatalkan',
        ];

        $totals = array_fill_keys([
            'total',
            'waiting',
            'called',
            'uncalled',
            'in_service',
            'completed',
            'no_show',
            'cancelled',
        ], 0);

        foreach ($clinics as $clinic) {
            foreach ($totals as $key => $total) {
                $totals[$key] = $total + $clinic[$key];
            }

            yield [
                $periodLabel,
                $this->safeCsvText($clinic['code']),
                $this->safeCsvText($clinic['name']),
                $clinic['total'],
                $clinic['waiting'],
                $clinic['called'],
                $clinic['uncalled'],
                $clinic['in_service'],
                $clinic['completed'],
                $clinic['no_show'],
                $clinic['cancelled'],
            ];
        }

        yield [
            $periodLabel,
            '',
            'Total',
            $totals['total'],
            $totals['waiting'],
            $totals['called'],
            $totals['uncalled'],
            $totals['in_service'],
            $totals['completed'],
            $totals['no_show'],
            $totals['cancelled'],
        ];
    }

    private function periodLabel(string $startDate, string $endDate): string
    {
        return $startDate === $endDate
            ? $startDate
            : "{$startDate} s.d. {$endDate}";
    }

    private function safeCsvText(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
            ? "'{$value}"
            : $value;
    }

    /**
     * @param  resource  $stream
     * @param  array<int, int|string>  $fields
     */
    private function writeCsvRow(mixed $stream, array $fields): void
    {
        if (fputcsv($stream, $fields, ',', '"', '', "\r\n") === false) {
            throw new RuntimeException('Tidak dapat menulis baris laporan CSV.');
        }
    }
}
