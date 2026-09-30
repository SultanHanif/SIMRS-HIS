<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $medications = Medication::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('strength', "%{$search}%")
                        ->orWhereLike('dosage_form', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('strength')
            ->orderBy('dosage_form')
            ->paginate(20)
            ->withQueryString();

        return view('pharmacy.medications.index', compact('medications', 'search'));
    }

    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        Medication::query()->create($request->validated());

        return back()->with('success', 'Obat berhasil ditambahkan ke katalog.');
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $medication->update($request->validated());

        return back()->with('success', 'Data obat berhasil diperbarui.');
    }
}
