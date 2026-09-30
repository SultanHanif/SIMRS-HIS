<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExaminationRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $allowedStatuses = ['waiting', 'in_consultation', 'awaiting_pharmacy', 'awaiting_payment', 'completed', 'no_show', 'cancelled'];
        $query = $this->visibleVisits($request);

        $visits = $query
            ->with(['patient', 'doctor', 'clinic', 'prescription', 'invoice'])
            ->when($request->user()->role === 'farmasi', fn (Builder $query) => $query->whereHas('prescription'))
            ->when($request->user()->role === 'kasir', fn (Builder $query) => $query->whereHas('invoice'))
            ->when(in_array($status, $allowedStatuses, true), fn (Builder $query) => $query->where('status', $status))
            ->latest('visited_at')
            ->paginate(12)
            ->withQueryString();

        return view('visits.index', compact('visits', 'status'));
    }

    public function create(Request $request): View
    {
        $patientSearch = trim($request->string('patient_search')->toString());
        $patients = Patient::query()
            ->when($patientSearch !== '', function (Builder $query) use ($patientSearch): void {
                $query->where(function (Builder $query) use ($patientSearch): void {
                    $query->whereLike('name', "%{$patientSearch}%")
                        ->orWhereLike('medical_record_number', "%{$patientSearch}%")
                        ->orWhereLike('national_id', "%{$patientSearch}%");
                });
            })
            ->when($patientSearch === '', fn (Builder $query) => $query->latest('created_at'))
            ->orderBy('name')
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'medical_record_number', 'name']);

        return view('visits.create', [
            'patients' => $patients,
            'patientSearch' => $patientSearch,
            'doctors' => Doctor::query()->where('status', 'active')->orderBy('name')->get(['id', 'clinic_id', 'name', 'specialization']),
            'clinics' => Clinic::query()->where('status', 'active')->where('consultation_fee', '>', 0)->orderBy('name')->get(['id', 'name', 'consultation_fee']),
        ]);
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $queueDate = Carbon::parse($data['visited_at'])->toDateString();

        $visit = DB::transaction(function () use ($data, $queueDate, $request): Visit {
            $clinic = Clinic::query()->findOrFail($data['clinic_id']);
            $queueNumber = $clinic->allocateQueueNumberForDate($queueDate);

            return Visit::create([
                ...$data,
                'visit_number' => 'RJ-'.Str::upper(Str::random(10)),
                'queue_date' => $queueDate,
                'queue_number' => $queueNumber,
                'registered_by' => $request->user()->id,
                'visit_type' => 'outpatient',
                'status' => 'waiting',
            ]);
        }, attempts: 5);

        return redirect()
            ->route('visits.show', $visit)
            ->with('success', 'Nomor antrean '.$visit->formattedQueueNumber().' berhasil dibuat.');
    }

    public function show(Request $request, Visit $visit): View
    {
        $this->authorizeVisit($request, $visit);
        $visit->load(['patient', 'doctor', 'clinic', 'prescription', 'invoice']);
        $previousExaminations = collect();

        if ($request->user()->hasRole('dokter', 'super_admin')) {
            $visit->load('examination');
            $previousExaminations = $visit->patient->visits()
                ->where('id', '!=', $visit->id)
                ->where('visited_at', '<', $visit->visited_at)
                ->whereHas('examination')
                ->with([
                    'examination:id,visit_id,doctor_id,diagnosis_code,diagnosis,notes,plan,systolic_pressure,diastolic_pressure,temperature_c,pulse_rate,weight_kg,height_cm',
                    'doctor:id,name,specialization',
                    'clinic:id,name',
                ])
                ->orderByDesc('visited_at')
                ->limit(5)
                ->get(['id', 'patient_id', 'clinic_id', 'doctor_id', 'visit_number', 'visited_at']);
        }

        $medicationSearch = trim($request->string('medication_search')->toString());
        $medications = collect();

        if ($request->user()->hasRole('dokter', 'super_admin') && $visit->status === 'in_consultation') {
            $medications = Medication::query()
                ->where('status', 'active')
                ->when($medicationSearch !== '', function (Builder $query) use ($medicationSearch): void {
                    $query->where(function (Builder $query) use ($medicationSearch): void {
                        $query->whereLike('name', "%{$medicationSearch}%")
                            ->orWhereLike('strength', "%{$medicationSearch}%")
                            ->orWhereLike('dosage_form', "%{$medicationSearch}%");
                    });
                })
                ->orderBy('name')
                ->orderBy('strength')
                ->orderBy('dosage_form')
                ->limit(30)
                ->get();
        }

        return view('visits.show', compact('medicationSearch', 'medications', 'previousExaminations', 'visit'));
    }

    public function ticket(Visit $visit): View
    {
        $visit->load(['patient', 'doctor', 'clinic']);

        return view('visits.ticket', compact('visit'));
    }

    public function receipt(Visit $visit): View
    {
        $visit->load(['patient', 'doctor', 'clinic', 'prescription', 'invoice.paidBy']);
        abort_unless($visit->invoice?->status === 'paid', 409, 'Kuitansi hanya tersedia setelah pembayaran lunas.');

        return view('visits.receipt', compact('visit'));
    }

    public function start(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctorAssignment($request, $visit);
        $this->expectStatus($visit, 'waiting');
        $visit->update(['status' => 'in_consultation']);

        return back()->with('success', 'Pemeriksaan dimulai.');
    }

    public function examine(StoreExaminationRequest $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctorAssignment($request, $visit);
        $this->expectStatus($visit, 'in_consultation');

        DB::transaction(function () use ($request, $visit): void {
            $data = $request->validated();

            Examination::create([
                'visit_id' => $visit->id,
                'doctor_id' => $visit->doctor_id,
                'diagnosis_code' => $data['diagnosis_code'] ?? null,
                'diagnosis' => $data['diagnosis'],
                'notes' => $data['notes'],
                'plan' => $data['plan'] ?? null,
                'systolic_pressure' => $data['systolic_pressure'] ?? null,
                'diastolic_pressure' => $data['diastolic_pressure'] ?? null,
                'temperature_c' => $data['temperature_c'] ?? null,
                'pulse_rate' => $data['pulse_rate'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
            ]);

            Invoice::create([
                'visit_id' => $visit->id,
                'amount' => $visit->clinic->consultation_fee,
            ]);

            if (filled($data['medication_id'] ?? null)) {
                $medication = Medication::query()
                    ->whereKey($data['medication_id'])
                    ->where('status', 'active')
                    ->firstOrFail();

                Prescription::create([
                    'visit_id' => $visit->id,
                    'medication_id' => $medication->id,
                    'prescribed_by' => $request->user()->id,
                    'medication_name' => $medication->displayName(),
                    'quantity' => $data['quantity'],
                    'unit_price' => $medication->unit_price,
                    'total_price' => $medication->unit_price * $data['quantity'],
                    'dosage' => $data['dosage'],
                    'instructions' => $data['instructions'] ?? null,
                ]);

                $visit->update(['status' => 'awaiting_pharmacy']);

                return;
            }

            $visit->update(['status' => 'awaiting_payment']);
        });

        return back()->with('success', 'Catatan pemeriksaan tersimpan.');
    }

    public function dispense(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->status === 'awaiting_pharmacy', 409, 'Kunjungan belum menunggu proses farmasi.');

        DB::transaction(function () use ($request, $visit): void {
            $prescription = $visit->prescription()->lockForUpdate()->first();

            if (! $prescription || $prescription->status !== 'pending') {
                throw ValidationException::withMessages([
                    'prescription' => 'Resep tidak tersedia atau sudah diproses.',
                ]);
            }

            $invoice = $visit->invoice()->lockForUpdate()->first();

            if (! $invoice || $invoice->status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan tidak tersedia atau sudah dibayar.',
                ]);
            }

            $prescription->update([
                'status' => 'dispensed',
                'dispensed_by' => $request->user()->id,
                'dispensed_at' => now(),
            ]);

            $invoice->increment('amount', $prescription->total_price);

            $visit->update(['status' => 'awaiting_payment']);
        });

        return back()->with('success', 'Obat telah diserahkan dan kunjungan diteruskan ke kasir.');
    }

    public function pay(StorePaymentRequest $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->status === 'awaiting_payment', 409, 'Kunjungan belum siap untuk pembayaran.');

        DB::transaction(function () use ($request, $visit): void {
            $invoice = $visit->invoice()->lockForUpdate()->first();

            if (! $invoice || $invoice->status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan tidak tersedia atau sudah dibayar.',
                ]);
            }

            $data = $request->validated();
            $amountReceived = (int) $data['amount_received'];

            $invoice->update([
                'status' => 'paid',
                'payment_method' => $data['payment_method'],
                'amount_received' => $amountReceived,
                'change_amount' => $amountReceived - $invoice->amount,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_notes' => $data['payment_notes'] ?? null,
                'paid_by' => $request->user()->id,
                'paid_at' => now(),
            ]);

            $visit->update(['status' => 'completed']);
        });

        return back()->with('success', 'Pembayaran berhasil dicatat. Kunjungan selesai.');
    }

    private function visibleVisits(Request $request): Builder
    {
        $query = Visit::query();

        if ($request->user()->role === 'dokter') {
            $query->whereHas('doctor', fn (Builder $doctorQuery) => $doctorQuery->where('user_id', $request->user()->id));
        }

        return $query;
    }

    private function authorizeVisit(Request $request, Visit $visit): void
    {
        if ($request->user()->role === 'dokter') {
            $this->authorizeDoctorAssignment($request, $visit);
        }

        if ($request->user()->role === 'farmasi') {
            abort_unless($visit->prescription()->exists(), 403);
        }

        if ($request->user()->role === 'kasir') {
            abort_unless($visit->invoice()->exists(), 403);
        }
    }

    private function authorizeDoctorAssignment(Request $request, Visit $visit): void
    {
        abort_unless(
            $request->user()->role === 'super_admin'
                || $visit->doctor()->where('user_id', $request->user()->id)->exists(),
            403,
        );
    }

    private function expectStatus(Visit $visit, string $status): void
    {
        abort_unless($visit->status === $status, 409, 'Status kunjungan tidak sesuai untuk tindakan ini.');
    }
}
