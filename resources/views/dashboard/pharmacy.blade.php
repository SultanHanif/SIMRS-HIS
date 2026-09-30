@extends('layouts.app')

@section('title', 'Workspace Farmasi')

@section('content')
    <div class="space-y-7">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-28 -z-10 h-80 w-80 rounded-full border-[38px] border-white/5"></div>
            <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-1/3 -z-10 h-40 w-72 rounded-full bg-violet-400/10 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="flex items-center gap-2 text-sm font-semibold text-violet-100">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-300"></span>
                        {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                    </p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Workspace Farmasi</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-200/75">Kelola resep yang menunggu penyiapan dan tinjau penyerahan obat hari ini.</p>
                </div>
                <a href="{{ route('visits.index', ['status' => 'awaiting_pharmacy']) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                    Buka seluruh antrean resep
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </section>

        <section aria-label="Ringkasan farmasi" class="grid gap-4 sm:grid-cols-2">
            <article class="app-surface rounded-2xl border border-slate-200/80 bg-white p-5">
                <p class="text-sm font-medium text-slate-500">Resep menunggu</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-violet-900">{{ number_format($pendingCount) }}</p>
                <p class="mt-3 text-xs text-slate-400">Resep berstatus menunggu yang siap diproses.</p>
            </article>
            <article class="app-surface rounded-2xl border border-slate-200/80 bg-white p-5">
                <p class="text-sm font-medium text-slate-500">Diserahkan hari ini</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-800">{{ number_format($dispensedTodayCount) }}</p>
                <p class="mt-3 text-xs text-slate-400">Resep yang telah dikonfirmasi diserahkan hari ini.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-slate-900">Antrean resep</h3>
                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-800">{{ number_format($pendingCount) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Menampilkan hingga 10 resep terlama lebih dahulu. Buka rincian untuk memeriksa dan mengonfirmasi penyerahan.</p>
                </div>
            </div>
            @if ($pendingVisits->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($pendingVisits as $visit)
                        <a href="{{ route('visits.show', $visit) }}" class="flex flex-col gap-4 px-5 py-4 transition hover:bg-violet-50/40 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <span class="flex min-w-0 items-start gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-800">{{ $visit->formattedQueueNumber() }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $visit->patient->medical_record_number }} <span class="px-1 text-slate-300">·</span> {{ $visit->clinic->name }} <span class="px-1 text-slate-300">·</span> {{ $visit->doctor->name }}</span>
                                    <span class="mt-2 block text-xs text-slate-400">{{ $visit->visited_at->format('d/m/Y H:i') }} <span class="px-1 text-slate-300">·</span> {{ $visit->visit_number }}</span>
                                </span>
                            </span>
                            <span class="flex min-w-0 flex-col gap-1 sm:max-w-sm sm:text-right">
                                <span class="text-sm font-semibold text-violet-900">{{ $visit->prescription->medication_name }}</span>
                                <span class="text-xs text-slate-600">Dosis: {{ $visit->prescription->dosage }}</span>
                                @if ($visit->prescription->instructions)
                                    <span class="text-xs leading-5 text-slate-500">{{ $visit->prescription->instructions }}</span>
                                @endif
                            </span>
                            <span class="inline-flex w-fit shrink-0 items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/15">Menunggu</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center px-6 py-12 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada resep yang menunggu</p>
                    <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">Resep baru akan muncul di sini setelah dokter meneruskannya ke farmasi.</p>
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900">Penyerahan hari ini</h3>
                <p class="mt-1 text-xs text-slate-500">Resep yang telah dikonfirmasi beserta petugas farmasi yang memprosesnya.</p>
            </div>
            @if ($recentlyDispensed->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($recentlyDispensed as $prescription)
                        <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $prescription->medication_name }} <span class="font-normal text-slate-400">· {{ $prescription->dosage }}</span></p>
                                <p class="mt-1 text-xs text-slate-500">{{ $prescription->visit->patient->name }} <span class="px-1 text-slate-300">·</span> {{ $prescription->visit->clinic->name }} <span class="px-1 text-slate-300">·</span> {{ $prescription->visit->visit_number }}</p>
                            </div>
                            <p class="shrink-0 text-xs text-slate-500">{{ $prescription->dispensed_at->format('H:i') }} <span class="px-1 text-slate-300">·</span> {{ $prescription->dispensedBy?->name ?? 'Petugas tidak tercatat' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada penyerahan obat yang dicatat hari ini.</p>
            @endif
        </section>
    </div>
@endsection
