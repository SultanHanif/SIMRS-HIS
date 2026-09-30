<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DoctorController extends Controller
{
    public function index(): View
    {
        $doctors = Doctor::query()
            ->with(['clinic', 'user'])
            ->orderBy('name')
            ->paginate(20);

        $doctorUsers = User::query()
            ->where('role', 'dokter')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active']);

        $clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $assignedUserIds = Doctor::query()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(static fn (int|string $userId): int => (int) $userId)
            ->all();

        return view('admin.doctors.index', compact('doctors', 'doctorUsers', 'assignedUserIds', 'clinics'));
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        Doctor::create($request->validated());

        return back()->with('success', 'Profil dokter berhasil ditambahkan.');
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($request->validated());

        return back()->with('success', 'Profil dokter berhasil diperbarui.');
    }
}
