<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $clinics = Clinic::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        $validated = $request->validate([
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('status', 'active'),
            ],
        ]);
        $clinicId = $validated['clinic_id'] ?? $clinics->first()?->id;

        $visits = Visit::query()
            ->with(['patient', 'doctor'])
            ->where('clinic_id', $clinicId)
            ->whereDate('visited_at', today())
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'waiting' AND queue_priority = 'priority' THEN 0 ELSE 1 END")
            ->orderBy('visited_at')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();
        $statusCounts = Visit::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('visited_at', today())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $calledCount = Visit::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('visited_at', today())
            ->where('status', 'waiting')
            ->whereNotNull('called_at')
            ->count();
        $uncalledCount = Visit::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('visited_at', today())
            ->where('status', 'waiting')
            ->whereNull('called_at')
            ->count();

        return view('queue.index', compact('clinics', 'clinicId', 'visits', 'statusCounts', 'calledCount', 'uncalledCount'));
    }

    public function callNext(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('clinics', 'id')->where('status', 'active'),
            ],
        ]);

        $visit = DB::transaction(function () use ($request, $validated): ?Visit {
            $visit = Visit::query()
                ->with('patient')
                ->where('clinic_id', $validated['clinic_id'])
                ->whereDate('visited_at', today())
                ->where('status', 'waiting')
                ->whereNull('called_at')
                ->orderByRaw("CASE WHEN queue_priority = 'priority' THEN 0 ELSE 1 END")
                ->orderBy('visited_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $visit) {
                return null;
            }

            $visit->update([
                'called_at' => now(),
                'called_by' => $request->user()->id,
            ]);

            return $visit;
        });

        if (! $visit) {
            return $this->queueRedirect((int) $validated['clinic_id'])
                ->with('warning', 'Tidak ada pasien menunggu yang belum dipanggil di poli ini.');
        }

        return $this->queueRedirect((int) $validated['clinic_id'])
            ->with('success', 'Antrean '.$visit->formattedQueueNumber().' atas nama '.$visit->patient->name.' dipanggil.');
    }

    public function updatePriority(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'queue_priority' => ['required', Rule::in(['normal', 'priority'])],
        ]);
        $this->ensureVisitIsWaiting($visit);

        $visit->update(['queue_priority' => $validated['queue_priority']]);

        return $this->queueRedirect($visit->clinic_id)
            ->with('success', 'Prioritas antrean berhasil diperbarui.');
    }

    public function markNoShow(Request $request, Visit $visit): RedirectResponse
    {
        $this->ensureVisitIsWaiting($visit);

        $visit->update([
            'status' => 'no_show',
            'no_show_at' => now(),
            'no_show_by' => $request->user()->id,
        ]);

        return $this->queueRedirect($visit->clinic_id)
            ->with('success', 'Pasien ditandai tidak hadir.');
    }

    public function cancel(Request $request, Visit $visit): RedirectResponse
    {
        $this->ensureVisitIsWaiting($visit);

        $visit->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
        ]);

        return $this->queueRedirect($visit->clinic_id)
            ->with('success', 'Kunjungan berhasil dibatalkan.');
    }

    private function ensureVisitIsWaiting(Visit $visit): void
    {
        abort_unless($visit->status === 'waiting', 409, 'Hanya kunjungan yang masih menunggu yang dapat diubah dari antrean.');
    }

    private function queueRedirect(int $clinicId): RedirectResponse
    {
        return redirect()->route('queue.index', ['clinic_id' => $clinicId]);
    }
}
