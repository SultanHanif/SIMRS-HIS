@extends('layouts.app')

@section('title', 'Rincian kunjungan')

@section('content')
    @php
        $statusLabels = [
            'waiting' => 'Menunggu dokter',
            'in_consultation' => 'Dalam pemeriksaan',
            'awaiting_pharmacy' => 'Menunggu farmasi',
            'awaiting_payment' => 'Menunggu pembayaran',
            'completed' => 'Kunjungan selesai',
            'no_show' => 'Pasien tidak hadir',
            'cancelled' => 'Kunjungan dibatalkan',
        ];
        $statusStyles = [
            'waiting' => 'bg-amber-50 text-amber-700 ring-amber-600/15',
            'in_consultation' => 'bg-blue-50 text-blue-700 ring-blue-600/15',
            'awaiting_pharmacy' => 'bg-violet-50 text-violet-700 ring-violet-600/15',
            'awaiting_payment' => 'bg-orange-50 text-orange-700 ring-orange-600/15',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
            'no_show' => 'bg-rose-50 text-rose-700 ring-rose-600/15',
            'cancelled' => 'bg-slate-100 text-slate-600 ring-slate-500/10',
        ];
        $canSeeClinical = auth()->user()->hasRole('super_admin', 'dokter');
        $showPharmacyStage = $visit->prescription !== null
            || in_array($visit->status, ['waiting', 'in_consultation', 'awaiting_pharmacy'], true);
        $journeyStages = [
            ['Pendaftaran', 'waiting'],
            ['Pemeriksaan', 'in_consultation'],
        ];

        if ($showPharmacyStage) {
            $journeyStages[] = ['Farmasi', 'awaiting_pharmacy'];
        }

        $journeyStages[] = ['Pembayaran', 'awaiting_payment'];
        $journeyStages[] = ['Selesai', 'completed'];
        $stages = array_column($journeyStages, 1);
        $currentStage = array_search($visit->status, $stages, true);
        $currentStage = $currentStage === false ? -1 : $currentStage;
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('visits.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">← Kembali ke worklist</a>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Rincian kunjungan</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if (auth()->user()->hasRole('super_admin', 'administrasi'))
                    <a href="{{ route('visits.ticket', $visit) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-clinic-200 bg-white px-4 text-sm font-semibold text-clinic-800 transition hover:bg-clinic-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
                        Cetak tiket antrean
                    </a>
                @endif
                <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $statusStyles[$visit->status] ?? 'bg-slate-100 text-slate-700 ring-slate-500/15' }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $statusLabels[$visit->status] ?? $visit->status }}
                </span>
            </div>
        </div>

        @if (in_array($visit->status, ['no_show', 'cancelled'], true))
            <div role="status" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $statusLabels[$visit->status] }}; kunjungan ini tidak lagi berada dalam antrean aktif.
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 bg-gradient-to-r from-slate-950 to-clinic-900 px-5 py-6 text-white sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-xl font-bold ring-1 ring-white/15">{{ mb_strtoupper(mb_substr($visit->patient->name, 0, 1)) }}</span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.13em] text-teal-100/75">Pasien</p>
                        <h3 class="mt-1 text-xl font-bold">{{ $visit->patient->name }}</h3>
                        <p class="mt-1 text-sm text-white/65">{{ $visit->patient->medical_record_number }} <span class="px-1 text-white/30">·</span> {{ $visit->patient->sex }}</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-semibold uppercase tracking-[.13em] text-teal-100/75">Nomor antrean · {{ $visit->clinic->code }}</p>
                    <p class="mt-1 font-mono text-2xl font-bold tracking-wide">{{ $visit->formattedQueueNumber() }}</p>
                    <p class="mt-1 text-sm text-white/65">{{ $visit->visited_at->translatedFormat('d F Y, H:i') }}</p>
                    <p class="mt-1 text-xs text-white/50">No. kunjungan: {{ $visit->visit_number }}</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-3 sm:p-6">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Poli / unit layanan</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ $visit->clinic->name }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dokter</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ $visit->doctor->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $visit->doctor->specialization }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Metode pembayaran</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ ['general' => 'Umum', 'bpjs' => 'BPJS', 'insurance' => 'Asuransi'][$visit->payment_method] ?? $visit->payment_method }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Perjalanan pelayanan</h3>
            <ol @class([
                'mt-5 grid gap-4',
                'sm:grid-cols-4' => count($journeyStages) === 4,
                'sm:grid-cols-5' => count($journeyStages) === 5,
            ])>
                @foreach ($journeyStages as $index => [$label, $stage])
                    @php($stagePosition = $index)
                    <li class="flex items-center gap-3 sm:block">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            'bg-clinic-700 text-white' => $stagePosition <= $currentStage,
                            'bg-slate-100 text-slate-500' => $stagePosition > $currentStage,
                        ])>{{ $index + 1 }}</span>
                        <span class="text-sm font-semibold {{ $stagePosition <= $currentStage ? 'text-slate-800' : 'text-slate-400' }} sm:mt-2 sm:block">{{ $label }}</span>
                        @if ($index < count($journeyStages) - 1)
                            <span class="hidden h-px flex-1 bg-slate-200 sm:mt-[-1.2rem] sm:block"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($canSeeClinical)
            <section class="overflow-hidden rounded-2xl border border-clinic-100 bg-white shadow-sm">
                <div class="border-b border-clinic-100 bg-clinic-50/60 px-5 py-4 sm:px-6">
                    <h3 class="text-sm font-bold text-slate-900">Riwayat pemeriksaan sebelumnya</h3>
                    <p class="mt-1 text-xs text-slate-600">Maksimal lima pemeriksaan terdahulu untuk membantu kesinambungan pelayanan.</p>
                </div>
                @if ($previousExaminations->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($previousExaminations as $previousVisit)
                            <article class="p-5 sm:p-6">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">{{ $previousVisit->examination->diagnosis }}</h4>
                                        @if ($previousVisit->examination->diagnosis_code)
                                            <p class="mt-1 text-xs text-slate-500">Kode diagnosis: {{ $previousVisit->examination->diagnosis_code }}</p>
                                        @endif
                                    </div>
                                    <p class="shrink-0 text-xs font-semibold text-slate-500">{{ $previousVisit->visited_at->translatedFormat('d F Y, H:i') }}</p>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan pemeriksaan</p>
                                        <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $previousVisit->examination->notes }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dokter dan poli</p>
                                        <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ $previousVisit->doctor->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $previousVisit->clinic->name }}</p>
                                    </div>
                                    @if ($previousVisit->examination->plan)
                                        <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rencana terdahulu</p>
                                            <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $previousVisit->examination->plan }}</p>
                                        </div>
                                    @endif
                                    @if ($previousVisit->examination->systolic_pressure !== null || $previousVisit->examination->diastolic_pressure !== null || $previousVisit->examination->temperature_c !== null || $previousVisit->examination->pulse_rate !== null || $previousVisit->examination->weight_kg !== null || $previousVisit->examination->height_cm !== null)
                                        <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanda-tanda vital</p>
                                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-700">
                                                @if ($previousVisit->examination->systolic_pressure !== null || $previousVisit->examination->diastolic_pressure !== null)
                                                    <span>Tekanan darah: {{ $previousVisit->examination->systolic_pressure ?? '—' }}/{{ $previousVisit->examination->diastolic_pressure ?? '—' }} mmHg</span>
                                                @endif
                                                @if ($previousVisit->examination->temperature_c !== null)<span>Suhu: {{ $previousVisit->examination->temperature_c }} °C</span>@endif
                                                @if ($previousVisit->examination->pulse_rate !== null)<span>Nadi: {{ $previousVisit->examination->pulse_rate }} kali/menit</span>@endif
                                                @if ($previousVisit->examination->weight_kg !== null)<span>Berat: {{ $previousVisit->examination->weight_kg }} kg</span>@endif
                                                @if ($previousVisit->examination->height_cm !== null)<span>Tinggi: {{ $previousVisit->examination->height_cm }} cm</span>@endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada pemeriksaan terdahulu yang tercatat untuk pasien ini.</p>
                @endif
            </section>
        @endif

        @if ($canSeeClinical)
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h3 class="text-sm font-bold text-slate-900">Pemeriksaan dokter</h3>
                    <p class="mt-1 text-xs text-slate-500">Catatan klinis tersedia bagi tenaga klinis yang berwenang.</p>
                </div>
                @if ($visit->examination)
                    <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Diagnosis</p>
                            <p class="mt-1.5 font-semibold text-slate-800">{{ $visit->examination->diagnosis }}</p>
                            @if ($visit->examination->diagnosis_code)<p class="mt-1 text-xs text-slate-500">Kode: {{ $visit->examination->diagnosis_code }}</p>@endif
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan pemeriksaan</p>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $visit->examination->notes }}</p>
                        </div>
                        @if ($visit->examination->plan)
                            <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rencana tindak lanjut</p>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $visit->examination->plan }}</p>
                            </div>
                        @endif
                        @if ($visit->examination->systolic_pressure !== null || $visit->examination->diastolic_pressure !== null || $visit->examination->temperature_c !== null || $visit->examination->pulse_rate !== null || $visit->examination->weight_kg !== null || $visit->examination->height_cm !== null)
                            <div class="rounded-xl border border-clinic-100 bg-clinic-50/60 p-4 sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-clinic-800">Tanda-tanda vital</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    @if ($visit->examination->systolic_pressure !== null || $visit->examination->diastolic_pressure !== null)
                                        <div><p class="text-xs text-slate-500">Tekanan darah</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->examination->systolic_pressure ?? '—' }}/{{ $visit->examination->diastolic_pressure ?? '—' }} <span class="text-xs font-normal text-slate-500">mmHg</span></p></div>
                                    @endif
                                    @if ($visit->examination->temperature_c !== null)<div><p class="text-xs text-slate-500">Suhu</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->examination->temperature_c }} <span class="text-xs font-normal text-slate-500">°C</span></p></div>@endif
                                    @if ($visit->examination->pulse_rate !== null)<div><p class="text-xs text-slate-500">Nadi</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->examination->pulse_rate }} <span class="text-xs font-normal text-slate-500">kali/menit</span></p></div>@endif
                                    @if ($visit->examination->weight_kg !== null)<div><p class="text-xs text-slate-500">Berat badan</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->examination->weight_kg }} <span class="text-xs font-normal text-slate-500">kg</span></p></div>@endif
                                    @if ($visit->examination->height_cm !== null)<div><p class="text-xs text-slate-500">Tinggi badan</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->examination->height_cm }} <span class="text-xs font-normal text-slate-500">cm</span></p></div>@endif
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif (auth()->user()->hasRole('dokter', 'super_admin') && $visit->status === 'waiting')
                    <div class="p-5 sm:p-6">
                        <p class="mb-4 text-sm text-slate-600"><span class="font-semibold">Keluhan awal:</span> {{ $visit->complaint ?: 'Tidak ada keluhan yang dicatat.' }}</p>
                        <form method="POST" action="{{ route('visits.start', $visit) }}">
                            @csrf
                            <button class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">Mulai pemeriksaan</button>
                        </form>
                    </div>
                @elseif (auth()->user()->hasRole('dokter', 'super_admin') && $visit->status === 'in_consultation')
                    <form method="GET" action="{{ route('visits.show', $visit) }}" class="mx-5 mb-0 mt-5 flex flex-col gap-3 rounded-xl border border-clinic-100 bg-clinic-50/60 p-4 sm:mx-6 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="medication_search" class="mb-2 block text-xs font-semibold text-slate-600">Cari obat untuk resep berdasarkan nama, kekuatan, atau bentuk</label>
                            <input id="medication_search" name="medication_search" type="search" value="{{ $medicationSearch }}" placeholder="Contoh: Parasetamol, 500 mg, tablet" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        </div>
                        <div class="flex gap-2">
                            <button class="inline-flex h-10 items-center justify-center rounded-lg bg-clinic-700 px-4 text-xs font-semibold text-white hover:bg-clinic-800">Cari obat</button>
                            @if ($medicationSearch !== '')
                                <a href="{{ route('visits.show', $visit) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                            @endif
                        </div>
                    </form>
                    <form method="POST" action="{{ route('visits.examination', $visit) }}" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        @csrf
                        <div>
                            <label for="diagnosis" class="mb-2 block text-sm font-semibold text-slate-700">Diagnosis <span class="text-rose-600">*</span></label>
                            <input id="diagnosis" name="diagnosis" value="{{ old('diagnosis') }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                            @error('diagnosis') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="diagnosis_code" class="mb-2 block text-sm font-semibold text-slate-700">Kode diagnosis (opsional)</label>
                            <input id="diagnosis_code" name="diagnosis_code" value="{{ old('diagnosis_code') }}" placeholder="Contoh: kode ICD-10" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="mb-2 block text-sm font-semibold text-slate-700">Catatan pemeriksaan <span class="text-rose-600">*</span></label>
                            <textarea id="notes" name="notes" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">{{ old('notes') }}</textarea>
                            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <fieldset class="rounded-xl border border-slate-200 p-4">
                                <legend class="px-1 text-sm font-semibold text-slate-800">Tanda-tanda vital <span class="font-normal text-slate-500">(opsional)</span></legend>
                                <div class="mt-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div>
                                        <label for="systolic_pressure" class="mb-2 block text-xs font-semibold text-slate-600">Tekanan sistolik (mmHg)</label>
                                        <input id="systolic_pressure" name="systolic_pressure" type="number" min="0" max="999" step="1" value="{{ old('systolic_pressure') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('systolic_pressure') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="diastolic_pressure" class="mb-2 block text-xs font-semibold text-slate-600">Tekanan diastolik (mmHg)</label>
                                        <input id="diastolic_pressure" name="diastolic_pressure" type="number" min="0" max="999" step="1" value="{{ old('diastolic_pressure') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('diastolic_pressure') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="temperature_c" class="mb-2 block text-xs font-semibold text-slate-600">Suhu (°C)</label>
                                        <input id="temperature_c" name="temperature_c" type="number" min="0" max="99.9" step="0.1" value="{{ old('temperature_c') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('temperature_c') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="pulse_rate" class="mb-2 block text-xs font-semibold text-slate-600">Nadi (kali/menit)</label>
                                        <input id="pulse_rate" name="pulse_rate" type="number" min="0" max="999" step="1" value="{{ old('pulse_rate') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('pulse_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="weight_kg" class="mb-2 block text-xs font-semibold text-slate-600">Berat badan (kg)</label>
                                        <input id="weight_kg" name="weight_kg" type="number" min="0" max="9999.99" step="0.01" value="{{ old('weight_kg') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('weight_kg') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="height_cm" class="mb-2 block text-xs font-semibold text-slate-600">Tinggi badan (cm)</label>
                                        <input id="height_cm" name="height_cm" type="number" min="0" max="999.99" step="0.01" value="{{ old('height_cm') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                        @error('height_cm') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="plan" class="mb-2 block text-sm font-semibold text-slate-700">Rencana tindak lanjut</label>
                            <textarea id="plan" name="plan" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">{{ old('plan') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <div class="rounded-xl border border-clinic-100 bg-clinic-50/60 p-4">
                                <p class="text-sm font-semibold text-slate-800">Resep digital <span class="font-normal text-slate-500">(opsional)</span></p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="medication_id" class="mb-2 block text-xs font-semibold text-slate-600">Obat dalam katalog</label>
                                        <select id="medication_id" name="medication_id" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                                            <option value="">Tanpa resep obat</option>
                                            @foreach ($medications as $medication)
                                                <option value="{{ $medication->id }}" @selected((string) old('medication_id') === (string) $medication->id)>{{ $medication->displayName() }} — Rp {{ number_format($medication->unit_price, 0, ',', '.') }} / satuan</option>
                                            @endforeach
                                        </select>
                                        @error('medication_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        @if ($medications->isEmpty())
                                            <p class="mt-1 text-xs text-amber-700">{{ $medicationSearch !== '' ? 'Tidak ada obat aktif yang cocok dengan pencarian tersebut.' : 'Katalog belum memiliki obat aktif. Cari obat atau hubungi petugas Farmasi untuk menambahkannya.' }}</p>
                                        @elseif ($medicationSearch === '' && $medications->count() === 30)
                                            <p class="mt-1 text-xs text-slate-500">Menampilkan 30 obat pertama. Gunakan pencarian untuk menemukan obat lainnya.</p>
                                        @endif
                                    </div>
                                    <div><label for="dosage" class="mb-2 block text-xs font-semibold text-slate-600">Dosis / aturan pakai</label><input id="dosage" name="dosage" value="{{ old('dosage') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100"></div>
                                    <div><label for="quantity" class="mb-2 block text-xs font-semibold text-slate-600">Jumlah obat</label><input id="quantity" name="quantity" type="number" min="1" max="10000" step="1" value="{{ old('quantity', 1) }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100"></div>
                                    <div class="sm:col-span-2"><label for="instructions" class="mb-2 block text-xs font-semibold text-slate-600">Instruksi untuk farmasi</label><textarea id="instructions" name="instructions" rows="2" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">{{ old('instructions') }}</textarea></div>
                                </div>
                            </div>
                        </div>
                        <div class="sm:col-span-2"><button class="inline-flex min-h-10 items-center justify-center rounded-xl bg-clinic-700 px-5 text-sm font-semibold text-white hover:bg-clinic-800">Simpan pemeriksaan & lanjutkan</button></div>
                    </form>
                @endif
            </section>
        @endif

        @if ($visit->prescription && auth()->user()->hasRole('farmasi', 'dokter', 'super_admin'))
            <section class="rounded-2xl border border-violet-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-violet-700">Resep digital</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900">{{ $visit->prescription->medication_name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $visit->prescription->dosage }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($visit->prescription->quantity) }} satuan × Rp {{ number_format($visit->prescription->unit_price, 0, ',', '.') }} = Rp {{ number_format($visit->prescription->total_price, 0, ',', '.') }}</p>
                        @if ($visit->prescription->instructions)<p class="mt-1 text-xs text-slate-500">{{ $visit->prescription->instructions }}</p>@endif
                        <p class="mt-2 text-xs font-semibold {{ $visit->prescription->status === 'dispensed' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $visit->prescription->status === 'dispensed' ? 'Obat telah diserahkan' : 'Menunggu diproses farmasi' }}</p>
                    </div>
                    @if (auth()->user()->hasRole('farmasi', 'super_admin') && $visit->prescription->status === 'pending')
                        <form method="POST" action="{{ route('visits.dispense', $visit) }}">
                            @csrf
                            <button class="inline-flex min-h-10 items-center justify-center rounded-xl bg-violet-700 px-4 text-sm font-semibold text-white hover:bg-violet-800">Konfirmasi obat diserahkan</button>
                        </form>
                    @endif
                </div>
            </section>
        @endif

        @if ($visit->invoice && auth()->user()->hasRole('kasir', 'super_admin'))
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tagihan kunjungan</p>
                        <p class="mt-1 text-2xl font-bold {{ $visit->invoice->status === 'paid' ? 'text-emerald-700' : 'text-slate-900' }}">Rp {{ number_format($visit->invoice->amount, 0, ',', '.') }}</p>
                        @if ($visit->invoice->status === 'paid')
                            <p class="mt-1 text-sm text-emerald-700">Lunas · {{ $visit->invoice->paid_at?->format('d/m/Y H:i') }}</p>
                            <a href="{{ route('visits.receipt', $visit) }}" target="_blank" rel="noopener" class="print-hidden mt-3 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                Lihat / cetak kuitansi
                            </a>
                        @else
                            <p class="mt-1 text-sm text-slate-600">Tagihan sesuai tarif konsultasi poli.</p>
                        @endif
                    </div>
                    @if ($visit->invoice->status === 'unpaid' && $visit->status === 'awaiting_payment')
                        <form method="POST" action="{{ route('visits.payment', $visit) }}" class="grid gap-3 sm:min-w-[22rem]">
                            @csrf
                            <div>
                                <label for="payment_method" class="mb-1 block text-xs font-semibold text-slate-600">Metode pembayaran</label>
                                <select id="payment_method" name="payment_method" required class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                    @foreach (['cash' => 'Tunai', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer bank', 'card' => 'Kartu debit/kredit'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="amount_received" class="mb-1 block text-xs font-semibold text-slate-600">Nominal diterima</label>
                                <input id="amount_received" name="amount_received" type="number" min="{{ $visit->invoice->amount }}" step="1" value="{{ old('amount_received', $visit->invoice->amount) }}" required class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm tabular-nums focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                <p class="mt-1 text-xs text-slate-500">Untuk non-tunai, nominal harus tepat sebesar tagihan.</p>
                            </div>
                            <div>
                                <label for="payment_reference" class="mb-1 block text-xs font-semibold text-slate-600">No. referensi <span class="font-normal text-slate-400">(wajib selain tunai)</span></label>
                                <input id="payment_reference" name="payment_reference" type="text" maxlength="100" value="{{ old('payment_reference') }}" placeholder="Contoh: TRX-123456" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            </div>
                            <div>
                                <label for="payment_notes" class="mb-1 block text-xs font-semibold text-slate-600">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                                <input id="payment_notes" name="payment_notes" type="text" maxlength="500" value="{{ old('payment_notes') }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            </div>
                            <button class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Konfirmasi pembayaran</button>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection
