<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('super_admin', 'administrasi')) {
            $todayVisits = Visit::query()->whereDate('visited_at', today());
            $visitsToday = (clone $todayVisits)->count();
            $waitingCount = (clone $todayVisits)->where('status', 'waiting')->count();
            $calledCount = (clone $todayVisits)
                ->where('status', 'waiting')
                ->whereNotNull('called_at')
                ->count();
            $uncalledCount = (clone $todayVisits)
                ->where('status', 'waiting')
                ->whereNull('called_at')
                ->count();
            $activeCount = (clone $todayVisits)
                ->whereIn('status', ['in_consultation', 'awaiting_pharmacy', 'awaiting_payment'])
                ->count();
            $completedCount = (clone $todayVisits)->where('status', 'completed')->count();
            $visitsByClinic = (clone $todayVisits)
                ->selectRaw('clinic_id, status, count(*) as aggregate')
                ->groupBy('clinic_id', 'status')
                ->get()
                ->groupBy('clinic_id');
            $clinicSummaries = Clinic::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function (Clinic $clinic) use ($visitsByClinic): array {
                    $statusCounts = $visitsByClinic
                        ->get($clinic->id, collect())
                        ->pluck('aggregate', 'status');

                    return [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'waiting' => (int) ($statusCounts['waiting'] ?? 0),
                        'in_consultation' => (int) ($statusCounts['in_consultation'] ?? 0),
                        'awaiting_pharmacy' => (int) ($statusCounts['awaiting_pharmacy'] ?? 0),
                        'awaiting_payment' => (int) ($statusCounts['awaiting_payment'] ?? 0),
                        'completed' => (int) ($statusCounts['completed'] ?? 0),
                        'no_show' => (int) ($statusCounts['no_show'] ?? 0),
                        'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                    ];
                });
            $taskVisits = (clone $todayVisits)
                ->with(['patient', 'doctor', 'clinic'])
                ->where('status', 'waiting')
                ->orderByRaw("CASE WHEN queue_priority = 'priority' THEN 0 ELSE 1 END")
                ->orderBy('visited_at')
                ->orderBy('id')
                ->limit(6)
                ->get();

            return view('dashboard.front-office', [
                'patientCount' => Patient::count(),
                'visitsToday' => $visitsToday,
                'waitingCount' => $waitingCount,
                'calledCount' => $calledCount,
                'uncalledCount' => $uncalledCount,
                'activeCount' => $activeCount,
                'completedCount' => $completedCount,
                'clinicSummaries' => $clinicSummaries,
                'taskVisits' => $taskVisits,
            ]);
        }

        if ($user->role === 'dokter') {
            return $this->doctorDashboard($user);
        }

        if ($user->role === 'farmasi') {
            return $this->pharmacyDashboard();
        }

        if ($user->role === 'kasir') {
            return $this->cashierDashboard();
        }

        $baseVisits = Visit::query();

        $visitsToday = (clone $baseVisits)->whereDate('visited_at', today())->count();
        $waitingCount = (clone $baseVisits)->where('status', 'waiting')->count();
        $activeCount = (clone $baseVisits)->whereIn('status', ['in_consultation', 'awaiting_pharmacy', 'awaiting_payment'])->count();
        $statusCounts = (clone $baseVisits)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $taskStatus = match ($user->role) {
            'kasir' => 'awaiting_payment',
            default => 'waiting',
        };

        $taskVisits = (clone $baseVisits)
            ->with(['patient', 'doctor', 'clinic', 'prescription', 'invoice'])
            ->where('status', $taskStatus)
            ->orderBy('visited_at')
            ->limit(8)
            ->get();

        $recentVisits = (clone $baseVisits)
            ->with(['patient', 'doctor', 'clinic'])
            ->when($user->role === 'kasir', fn (Builder $query) => $query->whereHas('invoice'))
            ->latest('visited_at')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'patientCount' => $user->hasRole('super_admin', 'administrasi') ? Patient::count() : 0,
            'visitsToday' => $visitsToday,
            'waitingCount' => $waitingCount,
            'activeCount' => $activeCount,
            'statusCounts' => $statusCounts,
            'taskStatus' => $taskStatus,
            'taskVisits' => $taskVisits,
            'recentVisits' => $recentVisits,
        ]);
    }

    private function doctorDashboard(User $user): View
    {
        $doctor = $user->doctorProfile()->with('clinic:id,name,code')->first();
        $todayVisits = Visit::query()
            ->where('doctor_id', $doctor?->id ?? 0)
            ->whereDate('visited_at', today());

        $summary = (clone $todayVisits)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting")
            ->selectRaw("SUM(CASE WHEN status = 'waiting' AND called_at IS NOT NULL THEN 1 ELSE 0 END) as ready")
            ->selectRaw("SUM(CASE WHEN status = 'waiting' AND called_at IS NULL THEN 1 ELSE 0 END) as not_called")
            ->selectRaw("SUM(CASE WHEN status = 'in_consultation' THEN 1 ELSE 0 END) as in_consultation")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->first();

        $queueVisits = (clone $todayVisits)
            ->with([
                'patient:id,medical_record_number,name',
                'clinic:id,name,code',
            ])
            ->where('status', 'waiting')
            ->orderByRaw('CASE WHEN called_at IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN queue_priority = 'priority' THEN 0 ELSE 1 END")
            ->orderBy('visited_at')
            ->limit(10)
            ->get();

        $inConsultationVisits = (clone $todayVisits)
            ->with([
                'patient:id,medical_record_number,name',
                'clinic:id,name,code',
            ])
            ->where('status', 'in_consultation')
            ->orderBy('visited_at')
            ->limit(5)
            ->get();

        return view('dashboard.doctor', [
            'doctor' => $doctor,
            'queueVisits' => $queueVisits,
            'inConsultationVisits' => $inConsultationVisits,
            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'waiting' => (int) ($summary->waiting ?? 0),
                'ready' => (int) ($summary->ready ?? 0),
                'not_called' => (int) ($summary->not_called ?? 0),
                'in_consultation' => (int) ($summary->in_consultation ?? 0),
                'completed' => (int) ($summary->completed ?? 0),
            ],
        ]);
    }

    private function pharmacyDashboard(): View
    {
        $pendingVisitsQuery = Visit::query()
            ->where('status', 'awaiting_pharmacy')
            ->whereHas('prescription', fn (Builder $query) => $query->where('status', 'pending'));
        $pendingCount = (clone $pendingVisitsQuery)->count();
        $pendingVisits = (clone $pendingVisitsQuery)
            ->with([
                'patient:id,name,medical_record_number',
                'doctor:id,name',
                'clinic:id,name,code',
                'prescription:id,visit_id,medication_name,dosage,instructions',
            ])
            ->orderBy('visited_at')
            ->orderBy('id')
            ->limit(10)
            ->get();
        $dispensedTodayCount = Prescription::query()
            ->where('status', 'dispensed')
            ->whereDate('dispensed_at', today())
            ->count();
        $recentlyDispensed = Prescription::query()
            ->with([
                'visit:id,patient_id,doctor_id,clinic_id,visit_number,visited_at',
                'visit.patient:id,name,medical_record_number',
                'visit.doctor:id,name',
                'visit.clinic:id,name,code',
                'dispensedBy:id,name',
            ])
            ->where('status', 'dispensed')
            ->whereDate('dispensed_at', today())
            ->latest('dispensed_at')
            ->limit(6)
            ->get();

        return view('dashboard.pharmacy', compact(
            'dispensedTodayCount',
            'pendingCount',
            'pendingVisits',
            'recentlyDispensed',
        ));
    }

    private function cashierDashboard(): View
    {
        $todayPaidInvoices = Invoice::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', today());
        $pendingInvoices = Invoice::query()
            ->where('status', 'unpaid')
            ->whereHas('visit', fn (Builder $query) => $query->where('status', 'awaiting_payment'));

        $pendingVisits = Visit::query()
            ->with(['patient:id,name,medical_record_number', 'clinic:id,name,code', 'doctor:id,name', 'invoice:id,visit_id,amount,status'])
            ->where('status', 'awaiting_payment')
            ->whereHas('invoice', fn (Builder $query) => $query->where('status', 'unpaid'))
            ->orderBy('visited_at')
            ->orderBy('id')
            ->limit(8)
            ->get();

        $recentPayments = (clone $todayPaidInvoices)
            ->with(['visit.patient:id,name,medical_record_number', 'visit.clinic:id,name,code', 'paidBy:id,name'])
            ->latest('paid_at')
            ->limit(6)
            ->get();

        return view('dashboard.cashier', [
            'pendingCount' => (clone $pendingInvoices)->count(),
            'pendingAmount' => (int) (clone $pendingInvoices)->sum('amount'),
            'paidTodayCount' => (clone $todayPaidInvoices)->count(),
            'paidTodayAmount' => (int) (clone $todayPaidInvoices)->sum('amount'),
            'pendingVisits' => $pendingVisits,
            'recentPayments' => $recentPayments,
        ]);
    }
}
