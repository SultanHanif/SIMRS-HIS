<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPossibleDuplicatePatientsRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('medical_record_number', "%{$search}%")
                        ->orWhereLike('national_id', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', compact('patient'));
    }

    public function visits(Patient $patient): View
    {
        $visits = $patient->visits()
            ->with(['clinic:id,name,code', 'doctor:id,name,specialization'])
            ->latest('visited_at')
            ->paginate(10);

        return view('patients.visits', compact('patient', 'visits'));
    }

    public function findPossibleDuplicates(FindPossibleDuplicatePatientsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $nationalId = trim($data['national_id'] ?? '');
        $name = trim($data['name'] ?? '');
        $dateOfBirth = $data['date_of_birth'] ?? null;

        if ($nationalId === '' && ($name === '' || $dateOfBirth === null)) {
            return response()->json(['matches' => []]);
        }

        $patients = Patient::query()
            ->where(function ($query) use ($nationalId, $name, $dateOfBirth): void {
                if ($nationalId !== '') {
                    $query->where('national_id', $nationalId);
                }

                if ($name !== '' && $dateOfBirth !== null) {
                    $query->orWhere(function ($query) use ($name, $dateOfBirth): void {
                        $query->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                            ->whereDate('date_of_birth', $dateOfBirth);
                    });
                }
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'medical_record_number', 'national_id', 'name', 'date_of_birth', 'sex'])
            ->map(function (Patient $patient) use ($nationalId, $name, $dateOfBirth): array {
                $matchedBy = [];

                if ($nationalId !== '' && $patient->national_id === $nationalId) {
                    $matchedBy[] = 'NIK';
                }

                if (
                    $name !== ''
                    && $dateOfBirth !== null
                    && Str::lower($patient->name) === Str::lower($name)
                    && $patient->date_of_birth?->toDateString() === $dateOfBirth
                ) {
                    $matchedBy[] = 'Nama dan tanggal lahir';
                }

                return [
                    'medical_record_number' => $patient->medical_record_number,
                    'name' => $patient->name,
                    'date_of_birth' => $patient->date_of_birth?->format('d/m/Y'),
                    'sex' => $patient->sex,
                    'matched_by' => $matchedBy,
                ];
            })
            ->values();

        return response()->json(['matches' => $patients]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create([
            ...$request->validated(),
            'medical_record_number' => 'RM-'.Str::upper(Str::random(12)),
        ]);

        return redirect()
            ->route('patients.index')
            ->with('success', "Data pasien {$patient->name} berhasil disimpan.");
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->safe()->only([
            'national_id',
            'name',
            'date_of_birth',
            'sex',
            'phone',
            'address',
        ]));

        return redirect()
            ->route('patients.index')
            ->with('success', "Data pasien {$patient->name} berhasil diperbarui.");
    }
}
