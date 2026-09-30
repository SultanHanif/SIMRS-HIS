@extends('layouts.app')

@section('title', 'Edit data pasien')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('patients.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">← Kembali ke pasien</a>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Edit data pasien</h2>
            <p class="mt-1 text-sm text-slate-500">Perbarui informasi pasien. Nomor rekam medis tidak dapat diubah.</p>
        </div>

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-sm font-bold text-slate-900">Identitas pasien</h3>
                <p class="mt-1 text-xs text-slate-500">Pastikan perubahan sesuai dokumen identitas pasien.</p>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="medical_record_number" class="mb-2 block text-sm font-semibold text-slate-700">Nomor rekam medis</label>
                    <input id="medical_record_number" value="{{ $patient->medical_record_number }}" readonly class="h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 text-sm font-semibold text-slate-600">
                </div>
                <div>
                    <label for="national_id" class="mb-2 block text-sm font-semibold text-slate-700">NIK</label>
                    <input id="national_id" name="national_id" value="{{ old('national_id', $patient->national_id) }}" inputmode="numeric" maxlength="32" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('national_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama lengkap <span class="text-rose-600">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $patient->name) }}" required autocomplete="name" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="mb-2 block text-sm font-semibold text-slate-700">Tanggal lahir</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" max="{{ today()->toDateString() }}" value="{{ old('date_of_birth', $patient->date_of_birth?->toDateString()) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('date_of_birth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sex" class="mb-2 block text-sm font-semibold text-slate-700">Jenis kelamin <span class="text-rose-600">*</span></label>
                    <select id="sex" name="sex" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Perempuan" @selected(old('sex', $patient->sex) === 'Perempuan')>Perempuan</option>
                        <option value="Laki-laki" @selected(old('sex', $patient->sex) === 'Laki-laki')>Laki-laki</option>
                    </select>
                    @error('sex') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-slate-700">Nomor telepon</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', $patient->phone) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="mb-2 block text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea id="address" name="address" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">{{ old('address', $patient->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <a href="{{ route('patients.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-clinic-700 px-5 text-sm font-semibold text-white transition hover:bg-clinic-800">Simpan perubahan</button>
            </div>
        </form>
    </div>
@endsection
