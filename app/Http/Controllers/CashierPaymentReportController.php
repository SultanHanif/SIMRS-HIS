<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CashierPaymentReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'required_with:date_to', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_with:date_from', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
        ], [
            'date_from.required_with' => 'Tanggal awal wajib dipilih jika tanggal akhir diisi.',
            'date_to.required_with' => 'Tanggal akhir wajib dipilih jika tanggal awal diisi.',
            'date_to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        $reportStartDate = (string) ($validated['date_from'] ?? $validated['date_to'] ?? today()->toDateString());
        $reportEndDate = (string) ($validated['date_to'] ?? $validated['date_from'] ?? today()->toDateString());
        $search = trim((string) ($validated['search'] ?? ''));
        $invoiceId = $this->invoiceIdFromSearch($search);

        $paymentsQuery = Invoice::query()
            ->with([
                'visit.patient:id,name,medical_record_number',
                'visit.clinic:id,name,code',
                'visit.doctor:id,name',
                'paidBy:id,name',
            ])
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $reportStartDate)
            ->whereDate('paid_at', '<=', $reportEndDate)
            ->when($search !== '', function (Builder $query) use ($search, $invoiceId): void {
                $query->where(function (Builder $query) use ($search, $invoiceId): void {
                    if ($invoiceId !== null) {
                        $query->whereKey($invoiceId)
                            ->orWhereHas('visit', fn (Builder $visitQuery) => $visitQuery->whereLike('visit_number', "%{$search}%"));
                    } else {
                        $query->whereHas('visit', fn (Builder $visitQuery) => $visitQuery->whereLike('visit_number', "%{$search}%"));
                    }

                    $query->orWhereHas('visit.patient', function (Builder $patientQuery) use ($search): void {
                        $patientQuery->whereLike('name', "%{$search}%")
                            ->orWhereLike('medical_record_number', "%{$search}%");
                    })
                        ->orWhereHas('visit.clinic', fn (Builder $clinicQuery) => $clinicQuery->whereLike('name', "%{$search}%"))
                        ->orWhereHas('paidBy', fn (Builder $userQuery) => $userQuery->whereLike('name', "%{$search}%"));
                });
            });

        $totalTransactions = (clone $paymentsQuery)->count();
        $totalAmount = (int) (clone $paymentsQuery)->sum('amount');
        $payments = (clone $paymentsQuery)
            ->latest('paid_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.payments', compact(
            'payments',
            'reportEndDate',
            'reportStartDate',
            'search',
            'totalAmount',
            'totalTransactions',
        ));
    }

    private function invoiceIdFromSearch(string $search): ?int
    {
        $normalizedSearch = Str::of($search)->upper()->replaceStart('INV-', '')->toString();

        return ctype_digit($normalizedSearch) ? (int) $normalizedSearch : null;
    }
}
