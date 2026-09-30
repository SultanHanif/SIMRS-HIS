@extends('layouts.app')

@section('title', 'Pendaftaran kunjungan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('visits.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">← Kembali ke worklist</a>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Pendaftaran kunjungan</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih pasien dan jadwal layanan untuk membuat antrean rawat jalan.</p>
        </div>

        @if ($patients->isEmpty() || $doctors->isEmpty() || $clinics->isEmpty())
            <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Data master belum lengkap.</p>
                <p class="mt-1">Pendaftaran memerlukan minimal satu pasien, dokter aktif, dan poli aktif dengan tarif konsultasi yang sudah ditetapkan administrator.</p>
            </div>
        @endif

        <form method="GET" action="{{ route('visits.create') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <label for="patient_search" class="sr-only">Cari pasien berdasarkan nama, nomor rekam medis, atau NIK</label>
            <input id="patient_search" name="patient_search" value="{{ $patientSearch }}" placeholder="Cari pasien dengan nama, nomor RM, atau NIK" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
            <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Cari pasien</button>
        </form>

        <form method="POST" action="{{ route('visits.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-clinic-50 text-clinic-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8v4h4v14H4V7h4V3Zm4 7v7m-3.5-3.5h7"/></svg>
                    </span>
                    <span><h3 class="text-sm font-bold text-slate-900">Rincian pendaftaran</h3><p class="mt-1 text-xs text-slate-500">Nomor kunjungan dan antrean dibuat otomatis.</p></span>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2">
                    <label for="patient_id" class="mb-2 block text-sm font-semibold text-slate-700">Pasien <span class="text-rose-600">*</span></label>
                    <select id="patient_id" name="patient_id" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Pilih pasien</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" @selected((string) old('patient_id') === (string) $patient->id)>{{ $patient->medical_record_number }} — {{ $patient->name }}</option>
                        @endforeach
                    </select>
                    @error('patient_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-slate-400">Belum terdaftar? <a href="{{ route('patients.create') }}" class="font-semibold text-clinic-700 hover:underline">Tambah data pasien</a></p>
                    <p class="mt-1 text-xs text-slate-400">{{ $patientSearch === '' ? 'Menampilkan maksimal 50 pasien terbaru.' : 'Hasil pencarian dibatasi hingga 50 pasien.' }}</p>
                </div>

                <div>
                    <label for="doctor_id" class="mb-2 block text-sm font-semibold text-slate-700">Dokter <span class="text-rose-600">*</span></label>
                    <select id="doctor_id" name="doctor_id" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Pilih dokter</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" data-clinic="{{ $doctor->clinic_id }}" @selected((string) old('doctor_id') === (string) $doctor->id)>{{ $doctor->name }}{{ $doctor->specialization ? ' · '.$doctor->specialization : '' }}</option>
                        @endforeach
                    </select>
                    @error('doctor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="clinic_id" class="mb-2 block text-sm font-semibold text-slate-700">Poli / unit layanan <span class="text-rose-600">*</span></label>
                    <select id="clinic_id" name="clinic_id" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Pilih poli</option>
                        @foreach ($clinics as $clinic)
                            <option value="{{ $clinic->id }}" @selected((string) old('clinic_id') === (string) $clinic->id)>{{ $clinic->name }}</option>
                        @endforeach
                    </select>
                    @error('clinic_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="visited_at" class="mb-2 block text-sm font-semibold text-slate-700">Waktu kunjungan <span class="text-rose-600">*</span></label>
                    <input id="visited_at" name="visited_at" type="datetime-local" value="{{ old('visited_at', now()->format('Y-m-d\TH:i')) }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('visited_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="payment_method" class="mb-2 block text-sm font-semibold text-slate-700">Jenis penjamin <span class="text-rose-600">*</span></label>
                    <select id="payment_method" name="payment_method" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="general" @selected(old('payment_method', 'general') === 'general')>Umum</option>
                    </select>
                    @error('payment_method') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="complaint" class="mb-2 block text-sm font-semibold text-slate-700">Keluhan awal</label>
                    <textarea id="complaint" name="complaint" rows="3" placeholder="Catat ringkasan keluhan pasien untuk membantu persiapan pemeriksaan." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">{{ old('complaint') }}</textarea>
                    @error('complaint') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs leading-5 text-slate-500">Setelah disimpan, kunjungan masuk ke antrean menunggu dokter.</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <a href="{{ route('visits.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button type="submit" @disabled($patients->isEmpty() || $doctors->isEmpty() || $clinics->isEmpty()) class="inline-flex min-h-10 items-center justify-center rounded-xl bg-clinic-700 px-5 text-sm font-semibold text-white hover:bg-clinic-800 disabled:cursor-not-allowed disabled:opacity-50">Simpan & buat antrean</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const doctorSelect = document.querySelector('#doctor_id');
        const clinicSelect = document.querySelector('#clinic_id');

        doctorSelect?.addEventListener('change', () => {
            const clinicId = doctorSelect.selectedOptions[0]?.dataset.clinic;

            if (clinicId) {
                clinicSelect.value = clinicId;
            }
        });
    </script>
@endpush
