<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ClinicController extends Controller
{
    public function index(): View
    {
        $clinics = Clinic::query()
            ->withCount('doctors')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.clinics.index', compact('clinics'));
    }

    public function store(StoreClinicRequest $request): RedirectResponse
    {
        Clinic::create($request->validated());

        return back()->with('success', 'Poli berhasil ditambahkan.');
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): RedirectResponse
    {
        $clinic->update($request->validated());

        return back()->with('success', 'Data poli berhasil diperbarui.');
    }
}
