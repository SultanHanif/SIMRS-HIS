@extends('layouts.app')

@section('title', 'Poli & tarif')

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm font-semibold text-clinic-700">Master pelayanan</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Poli & tarif konsultasi</h2>
            <p class="mt-1 text-sm text-slate-500">Tarif tersimpan pada tagihan saat dokter menyelesaikan pemeriksaan.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Tambah poli</h3>
            <form method="POST" action="{{ route('admin.clinics.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @csrf
                <label class="text-sm font-semibold text-slate-700">Kode poli
                    <input name="code" value="{{ old('code') }}" required maxlength="16" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700 lg:col-span-2">Nama poli
                    <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700">Tarif konsultasi (Rp)
                    <input name="consultation_fee" type="number" min="1" max="1000000000" step="1" value="{{ old('consultation_fee') }}" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <div class="flex items-end">
                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-clinic-700 px-4 text-sm font-semibold text-white hover:bg-clinic-800">Tambah poli</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($clinics->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($clinics as $clinic)
                        <form method="POST" action="{{ route('admin.clinics.update', $clinic) }}" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-[150px_1fr_200px_150px_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <label class="text-xs font-semibold text-slate-500">Kode
                                <input name="code" value="{{ $clinic->code }}" required maxlength="16" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Nama poli
                                <input name="name" value="{{ $clinic->name }}" required maxlength="255" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Tarif konsultasi (Rp)
                                <input name="consultation_fee" type="number" min="1" max="1000000000" step="1" value="{{ $clinic->consultation_fee }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Status
                                <select name="status" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    <option value="active" @selected($clinic->status === 'active')>Aktif</option>
                                    <option value="inactive" @selected($clinic->status === 'inactive')>Nonaktif</option>
                                </select>
                            </label>
                            <button class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan</button>
                            <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-5">{{ $clinic->doctors_count }} profil dokter terhubung · Rp {{ number_format($clinic->consultation_fee, 0, ',', '.') }} per konsultasi</p>
                        </form>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $clinics->links() }}</div>
            @else
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada poli. Tambahkan poli dan tarif sebelum membuka pendaftaran pasien.</p>
            @endif
        </section>
    </div>
@endsection
