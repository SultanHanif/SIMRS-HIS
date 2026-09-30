@extends('layouts.app')

@section('title', 'Dashboard Front Office')

@section('content')
    <div class="space-y-7">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-clinic-900 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-28 -z-10 h-80 w-80 rounded-full border-[38px] border-white/5"></div>
            <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-1/3 -z-10 h-40 w-72 rounded-full bg-clinic-400/10 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-col gap-1 text-sm font-semibold sm:flex-row sm:items-center sm:gap-3">
                        <p class="flex items-center gap-2 text-teal-100">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-teal-300"></span>
                            {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="text-white/70">Workspace Admin / Front Office</p>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat bertugas, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-200/75">Pantau pendaftaran dan antrean pasien hari ini dari satu ruang kerja.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('queue.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                        Kelola antrean
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('reports.operational') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                        Laporan harian
                    </a>
                    <a href="{{ route('visits.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-clinic-900 shadow-sm transition hover:bg-clinic-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                        Daftar kunjungan
                    </a>
                </div>
            </div>
        </section>

        <section aria-label="Ringkasan operasional hari ini" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ([
                ['Kunjungan hari ini', $visitsToday, 'Total registrasi semua poli', 'bg-blue-50 text-blue-700'],
                ['Menunggu', $waitingCount, 'Belum mulai diperiksa', 'bg-amber-50 text-amber-700'],
                ['Perlu dipanggil', $uncalledCount, 'Belum diumumkan ke pasien', 'bg-rose-50 text-rose-700'],
                ['Sudah dipanggil', $calledCount, 'Masih menunggu dokter', 'bg-violet-50 text-violet-700'],
                ['Dalam alur layanan', $activeCount, 'Pemeriksaan, farmasi, atau kasir', 'bg-indigo-50 text-indigo-700'],
                ['Selesai', $completedCount, 'Kunjungan hari ini', 'bg-emerald-50 text-emerald-700'],
            ] as [$label, $count, $description, $color])
                <article class="app-surface rounded-2xl border border-slate-200/80 bg-white p-4 transition hover:-translate-y-0.5 hover:border-clinic-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($count) }}</p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $color }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">{{ $description }}</p>
                </article>
            @endforeach
        </section>

        @if ($uncalledCount > 0)
            <a href="{{ route('queue.index') }}" class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-950 transition hover:border-amber-300 sm:flex-row sm:items-center sm:justify-between">
                <span class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18.5A2 2 0 0 0 3.55 21h16.9a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.46 0Z"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold">{{ number_format($uncalledCount) }} pasien masih perlu dipanggil</span>
                        <span class="mt-1 block text-xs leading-5 text-amber-800">Buka manajemen antrean untuk memilih poli dan memanggil pasien berikutnya.</span>
                    </span>
                </span>
                <span class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-amber-900 px-4 text-sm font-semibold text-white transition hover:bg-amber-800">
                    Buka antrean
                    <span aria-hidden="true">→</span>
                </span>
            </a>
        @endif

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,.8fr)]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Antrean yang menunggu</h3>
                        <p class="mt-1 text-xs text-slate-500">Prioritas didahulukan, lalu urutan waktu pendaftaran.</p>
                    </div>
                    <a href="{{ route('queue.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-clinic-700 hover:text-clinic-900">Kelola semua antrean <span aria-hidden="true">→</span></a>
                </div>

                @if ($taskVisits->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($taskVisits as $visit)
                            <a href="{{ route('visits.show', $visit) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $visit->queue_priority === 'priority' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                        <span class="text-sm font-bold">{{ mb_strtoupper(mb_substr($visit->patient->name, 0, 1)) }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                            @if ($visit->queue_priority === 'priority')
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800 ring-1 ring-inset ring-amber-600/15">Didahulukan</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block truncate text-xs text-slate-500">{{ $visit->clinic->name }} · {{ $visit->doctor->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ $visit->patient->medical_record_number }} · Antrean {{ $visit->formattedQueueNumber() }} · {{ $visit->visit_number }}</span>
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end">
                                    @if ($visit->called_at)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/15">Dipanggil {{ $visit->called_at->format('H:i') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/15">Belum dipanggil</span>
                                    @endif
                                    <span class="text-xs font-medium tabular-nums text-slate-400">{{ $visit->visited_at->format('H:i') }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4m5 2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada antrean yang menunggu</p>
                        <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">Pendaftaran pasien baru akan muncul di bagian ini.</p>
                        <a href="{{ route('visits.create') }}" class="mt-4 inline-flex min-h-10 items-center rounded-xl bg-clinic-700 px-4 text-sm font-semibold text-white transition hover:bg-clinic-800">Daftar kunjungan</a>
                    </div>
                @endif
            </div>

            <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Ringkasan poli hari ini</h3>
                    <p class="mt-1 text-xs text-slate-500">Jumlah antrean dan pelayanan per unit.</p>
                </div>
                @if ($clinicSummaries->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($clinicSummaries as $clinic)
                            <div class="px-5 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $clinic['name'] }}</p>
                                    <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">{{ number_format($clinic['waiting']) }} menunggu</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span>{{ number_format($clinic['in_consultation']) }} diperiksa</span>
                                    <span>{{ number_format($clinic['awaiting_pharmacy']) }} menunggu farmasi</span>
                                    <span>{{ number_format($clinic['awaiting_payment']) }} menunggu kasir</span>
                                    <span>{{ number_format($clinic['completed']) }} selesai</span>
                                    @if ($clinic['no_show'] > 0)
                                        <span class="text-rose-700">{{ number_format($clinic['no_show']) }} tidak hadir</span>
                                    @endif
                                    @if ($clinic['cancelled'] > 0)
                                        <span>{{ number_format($clinic['cancelled']) }} dibatalkan</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-800">Belum ada poli aktif</p>
                        <p class="mt-1 text-xs text-slate-500">Minta administrator menyiapkan unit layanan.</p>
                    </div>
                @endif
                <div class="border-t border-slate-100 px-5 py-4">
                    <a href="{{ route('patients.index') }}" class="flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        <span>{{ number_format($patientCount) }} pasien terdaftar</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </aside>
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-clinic-100 bg-gradient-to-r from-clinic-50 to-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-clinic-950">Akses cepat</h3>
                <p class="mt-1 text-xs text-slate-600">Pilih tindakan Front Office yang ingin dilanjutkan.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('patients.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-clinic-200 bg-white px-4 text-sm font-semibold text-clinic-800 transition hover:bg-clinic-50">Tambah pasien</a>
                <a href="{{ route('patients.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-clinic-200 bg-white px-4 text-sm font-semibold text-clinic-800 transition hover:bg-clinic-50">Cari data pasien</a>
                <a href="{{ route('visits.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-clinic-200 bg-white px-4 text-sm font-semibold text-clinic-800 transition hover:bg-clinic-50">Riwayat kunjungan</a>
            </div>
        </section>
    </div>
@endsection
