@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $roleLabel = [
            'super_admin' => 'Administrator',
            'administrasi' => 'Admin / Front Office',
            'dokter' => 'Dokter',
            'farmasi' => 'Farmasi',
            'kasir' => 'Kasir',
        ][auth()->user()->role] ?? 'Petugas';
        $taskTitle = match (auth()->user()->role) {
            'dokter' => 'Pasien menunggu pemeriksaan',
            'farmasi' => 'Resep menunggu farmasi',
            'kasir' => 'Tagihan menunggu pembayaran',
            default => 'Antrean pendaftaran hari ini',
        };
        $statusLabels = [
            'waiting' => 'Menunggu',
            'in_consultation' => 'Dalam pemeriksaan',
            'awaiting_pharmacy' => 'Menunggu farmasi',
            'awaiting_payment' => 'Menunggu pembayaran',
            'completed' => 'Selesai',
            'no_show' => 'Tidak hadir',
            'cancelled' => 'Dibatalkan',
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
    @endphp

    <div class="space-y-8">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-clinic-900 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-28 -z-10 h-80 w-80 rounded-full border-[38px] border-white/5"></div>
            <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-1/3 -z-10 h-40 w-72 rounded-full bg-clinic-400/10 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-col gap-1 text-sm font-semibold sm:flex-row sm:items-center sm:gap-3">
                        <p class="flex items-center gap-2 text-teal-100">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-teal-300"></span>
                            {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="text-white/70">Workspace {{ $roleLabel }}</p>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat bertugas, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-200/75">Pantau kebutuhan pelayanan dan lanjutkan pekerjaan pasien dari satu ruang kerja.</p>
                </div>
                <a href="{{ auth()->user()->hasRole('super_admin', 'administrasi') ? route('queue.index') : route('visits.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                    {{ auth()->user()->hasRole('super_admin', 'administrasi') ? 'Kelola antrean' : 'Buka worklist' }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </section>

        <section aria-label="Ringkasan operasional" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Kunjungan hari ini', $visitsToday, 'Registrasi pada seluruh unit layanan', 'bg-blue-50 text-blue-700'],
                ['Menunggu antrean', $waitingCount, 'Perlu ditindaklanjuti sesuai peran', 'bg-amber-50 text-amber-700'],
                ['Dalam alur layanan', $activeCount, 'Pemeriksaan, farmasi, atau pembayaran', 'bg-violet-50 text-violet-700'],
            ] as [$label, $count, $description, $color])
                <article class="app-surface group rounded-2xl border border-slate-200/80 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:border-clinic-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 transition group-hover:text-clinic-800">{{ number_format($count) }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $color }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">{{ $description }}</p>
                </article>
            @endforeach

            @if (auth()->user()->hasRole('super_admin', 'administrasi'))
                <a href="{{ route('patients.index') }}" class="app-surface group rounded-2xl border border-slate-200/80 bg-white p-5 transition hover:-translate-y-0.5 hover:border-clinic-200">
                    <p class="text-sm font-medium text-slate-500">Pasien terdaftar</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($patientCount) }}</p>
                    <p class="mt-3 text-xs font-semibold text-clinic-700">Buka data pasien <span class="transition group-hover:translate-x-1">→</span></p>
                </a>
            @else
                <a href="{{ route('visits.index') }}" class="group rounded-2xl border border-clinic-100 bg-clinic-50/70 p-5 transition hover:bg-clinic-50">
                    <p class="text-sm font-medium text-clinic-800">Tugas saya</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-clinic-900">{{ number_format($taskVisits->count()) }}</p>
                    <p class="mt-3 text-xs font-semibold text-clinic-800">{{ $taskTitle }}</p>
                </a>
            @endif
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(290px,.75fr)]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">{{ $taskTitle }}</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $taskVisits->count() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Daftar prioritas untuk langkah pelayanan berikutnya.</p>
                    </div>
                    <a href="{{ route('visits.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-clinic-700 hover:text-clinic-900">Semua kunjungan <span aria-hidden="true">→</span></a>
                </div>
                @if ($taskVisits->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($taskVisits as $visit)
                            <a href="{{ route('visits.show', $visit) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">{{ mb_strtoupper(mb_substr($visit->patient->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $visit->visit_number }} <span class="px-1 text-slate-300">·</span> {{ $visit->clinic->name }} <span class="px-1 text-slate-300">·</span> {{ $visit->visited_at->format('H:i') }}</span>
                                    </span>
                                </span>
                                <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$visit->status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10' }}">{{ $statusLabels[$visit->status] ?? $visit->status }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-clinic-50 text-clinic-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4m5 2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada tugas yang menunggu</p>
                        <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">Daftar kerja akan muncul di sini saat ada kunjungan yang perlu ditindaklanjuti.</p>
                    </div>
                @endif
            </div>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Alur kunjungan</h3>
                    <p class="mt-1 text-xs text-slate-500">Ringkasan status pelayanan</p>
                </div>
                <ol class="mt-5 space-y-4">
                    @foreach ([
                        ['Menunggu', 'waiting', 'bg-amber-400'],
                        ['Pemeriksaan', 'in_consultation', 'bg-blue-500'],
                        ['Farmasi', 'awaiting_pharmacy', 'bg-violet-500'],
                        ['Pembayaran', 'awaiting_payment', 'bg-orange-500'],
                        ['Selesai', 'completed', 'bg-emerald-500'],
                        ['Tidak hadir', 'no_show', 'bg-rose-500'],
                        ['Dibatalkan', 'cancelled', 'bg-slate-400'],
                    ] as [$label, $status, $dot])
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2.5 text-sm text-slate-600"><span class="h-2 w-2 rounded-full {{ $dot }}"></span>{{ $label }}</span>
                            <span class="text-sm font-bold tabular-nums text-slate-900">{{ number_format($statusCounts[$status] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('visits.index') }}" class="mt-6 flex min-h-10 w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Buka seluruh kunjungan</a>
            </aside>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div><h3 class="text-base font-bold text-slate-900">Aktivitas terbaru</h3><p class="mt-1 text-xs text-slate-500">Kunjungan terakhir yang tercatat dalam sistem.</p></div>
                <a href="{{ route('visits.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">Lihat worklist</a>
            </div>
            @if ($recentVisits->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr><th class="px-5 py-3">Nomor kunjungan</th><th class="px-5 py-3">Pasien</th><th class="px-5 py-3">Unit layanan</th><th class="px-5 py-3">Dokter</th><th class="px-5 py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($recentVisits as $visit)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-700"><a href="{{ route('visits.show', $visit) }}" class="hover:text-clinic-700">{{ $visit->visit_number }}</a><span class="mt-1 block text-xs font-normal text-slate-400">{{ $visit->visited_at->format('d/m/Y H:i') }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-slate-700">{{ $visit->patient->name }}<span class="mt-1 block text-xs text-slate-400">{{ $visit->patient->medical_record_number }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $visit->clinic->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $visit->doctor->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$visit->status] ?? '' }}">{{ $statusLabels[$visit->status] ?? $visit->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada kunjungan yang tercatat.</p>
            @endif
        </section>
    </div>
@endsection
