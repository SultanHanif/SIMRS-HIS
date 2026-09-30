@extends('layouts.app')

@section('title', 'Profil dokter')

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm font-semibold text-clinic-700">Master tenaga medis</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Profil dokter</h2>
            <p class="mt-1 text-sm text-slate-500">Hubungkan akun dokter aktif ke poli tempat praktiknya.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Tambah profil dokter</h3>
            @if ($doctorUsers->where('is_active', true)->whereNotIn('id', $assignedUserIds)->isEmpty() || $clinics->where('status', 'active')->isEmpty())
                <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Buat akun dengan peran Dokter dan poli aktif terlebih dahulu.</p>
            @else
                <form method="POST" action="{{ route('admin.doctors.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @csrf
                    <label class="text-sm font-semibold text-slate-700 lg:col-span-2">Akun dokter
                        <select name="user_id" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                            <option value="">Pilih akun</option>
                            @foreach ($doctorUsers->where('is_active', true)->whereNotIn('id', $assignedUserIds) as $account)
                                <option value="{{ $account->id }}" @selected((string) old('user_id') === (string) $account->id)>{{ $account->name }} · {{ $account->email }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Poli
                        <select name="clinic_id" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                            <option value="">Pilih poli</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected((string) old('clinic_id') === (string) $clinic->id)>{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Nama tampilan
                        <input name="name" value="{{ old('name') }}" required maxlength="255" placeholder="dr. Nama Dokter" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Spesialisasi
                        <input name="specialization" value="{{ old('specialization') }}" maxlength="255" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    </label>
                    <div class="flex items-end">
                        <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-clinic-700 px-4 text-sm font-semibold text-white hover:bg-clinic-800">Tambah dokter</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($doctors->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($doctors as $doctor)
                        <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_180px_140px_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <label class="text-xs font-semibold text-slate-500">Akun petugas
                                <select name="user_id" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    @foreach ($doctorUsers->filter(fn ($account) => ! in_array($account->id, $assignedUserIds, true) || $account->id === $doctor->user_id) as $account)
                                        <option value="{{ $account->id }}" @selected($doctor->user_id === $account->id)>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Nama tampilan
                                <input name="name" value="{{ $doctor->name }}" required maxlength="255" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Spesialisasi
                                <input name="specialization" value="{{ $doctor->specialization }}" maxlength="255" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Poli
                                <select name="clinic_id" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    @foreach ($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected($doctor->clinic_id === $clinic->id)>{{ $clinic->name }}{{ $clinic->status === 'inactive' ? ' (nonaktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Status
                                <select name="status" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    <option value="active" @selected($doctor->status === 'active')>Aktif</option>
                                    <option value="inactive" @selected($doctor->status === 'inactive')>Nonaktif</option>
                                </select>
                            </label>
                            <button class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan</button>
                        </form>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $doctors->links() }}</div>
            @else
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada profil dokter.</p>
            @endif
        </section>
    </div>
@endsection
