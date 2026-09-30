@extends('layouts.app')

@section('title', 'Dashboard Dokter')

@section('content')
    @php
        $summaryCards = [
            ['Jadwal hari ini', $summary['total'], 'Semua kunjungan saya hari ini', 'bg-blue-50 text-blue-700'],
            ['Siap diperiksa', $summary['ready'], 'Pasien telah dipanggil Front Office', 'bg-emerald-50 text-emerald-700'],
            ['Menunggu dipanggil', $summary['not_called'], 'Pasien belum dipanggil', 'bg-amber-50 text-amber-700'],
            ['Dalam pemeriksaan', $summary['in_consultation'], 'Pemeriksaan sedang berlangsung', 'bg-violet-50 text-violet-700'],
            ['Selesai hari ini', $summary['completed'], 'Kunjungan telah dituntaskan', 'bg-slate-100 text-slate-700'],
        ];
    @endphp

    <div class="space-y-7">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-clinic-900 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-28 -z-10 h-80 w-80 rounded-full border-[38px] border-white/5"></div>
            <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-1/3 -z-10 h-40 w-72 rounded-full bg-clinic-400/10 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="flex items-center gap-2 text-sm font-semibold text-teal-100">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-teal-300"></span>
                        {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                    </p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat bertugas, {{ auth()->user()->name }}</h2>
                    @if ($doctor)
                        <p class="mt-2 text-sm text-slate-200/75">
                            {{ $doctor->name }} · {{ $doctor->specialization }} · {{ $doctor->clinic->name }}
                        </p>
                    @else
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-200/75">
                            Profil dokter belum terhubung ke akun ini. Hubungi Administrator untuk mengatur penugasan agar daftar pasien dapat ditampilkan.
                        </p>
                    @endif
                </div>
                <a href="{{ route('visits.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                    Buka seluruh worklist
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </section>

        <section aria-label="Ringkasan kunjungan dokter hari ini" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($summaryCards as [$label, $count, $description, $color])
                <article class="app-surface rounded-2xl border border-slate-200/80 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($count) }}</p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $color }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-400">{{ $description }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.85fr)]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">Antrean pemeriksaan hari ini</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ number_format($summary['waiting']) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Menampilkan hingga 10 pasien; pasien yang sudah dipanggil ditampilkan lebih dahulu.</p>
                    </div>
                    <a href="{{ route('visits.index', ['status' => 'waiting']) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-clinic-700 hover:text-clinic-900">Semua yang menunggu <span aria-hidden="true">→</span></a>
                </div>

                @if ($queueVisits->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($queueVisits as $visit)
                            <a href="{{ route('visits.show', $visit) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $visit->queue_priority === 'priority' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                        <span class="text-sm font-bold">{{ $visit->formattedQueueNumber() }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $visit->patient->medical_record_number }} <span class="px-1 text-slate-300">·</span> {{ $visit->clinic->name }} <span class="px-1 text-slate-300">·</span> {{ $visit->visited_at->format('H:i') }}</span>
                                    </span>
                                </span>
                                <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $visit->called_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/15' : 'bg-amber-50 text-amber-700 ring-amber-600/15' }}">
                                    {{ $visit->called_at ? 'Sudah dipanggil' : 'Menunggu panggilan' }}
                                    @if ($visit->queue_priority === 'priority')
                                        <span class="ml-1">· Didahulukan</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-clinic-50 text-clinic-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4m5 2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada pasien menunggu hari ini</p>
                        <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">Pasien yang ditugaskan kepada Anda akan muncul di sini.</p>
                    </div>
                @endif
            </div>

            <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Sedang diperiksa</h3>
                        <p class="mt-1 text-xs text-slate-500">Kunjungan aktif yang perlu dilanjutkan.</p>
                    </div>
                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $inConsultationVisits->count() }}</span>
                </div>
                @if ($inConsultationVisits->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($inConsultationVisits as $visit)
                            <a href="{{ route('visits.show', $visit) }}" class="flex items-center justify-between gap-3 px-5 py-4 transition hover:bg-slate-50">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                    <span class="mt-1 block truncate text-xs text-slate-500">{{ $visit->patient->medical_record_number }} · {{ $visit->visited_at->format('H:i') }}</span>
                                </span>
                                <span class="shrink-0 rounded-lg bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Lanjutkan</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pemeriksaan yang sedang berlangsung.</p>
                @endif
            </aside>
        </section>
    </div>
@endsection
