@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
    <div class="space-y-6">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-28 -z-10 h-80 w-80 rounded-full border-[38px] border-white/5"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-emerald-100">{{ now()->locale('id')->translatedFormat('l, d F Y') }} · Workspace Kasir</p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat bertugas, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-200/75">Kelola tagihan yang perlu dibayar dan pantau penerimaan hari ini.</p>
                </div>
                <a href="{{ route('reports.payments') }}" class="print-hidden inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                    Buka laporan pembayaran
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </section>

        <section aria-label="Ringkasan pembayaran" class="grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-orange-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tagihan menunggu pembayaran</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($pendingCount) }}</p>
                <p class="mt-2 text-sm font-semibold text-orange-700">Rp {{ number_format($pendingAmount, 0, ',', '.') }} belum dibayar</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Pembayaran hari ini</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($paidTodayCount) }} transaksi</p>
                <p class="mt-2 text-sm font-semibold text-emerald-700">Rp {{ number_format($paidTodayAmount, 0, ',', '.') }} diterima</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,.8fr)]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">Antrean tagihan</h3>
                            <span class="rounded-full bg-orange-50 px-2 py-0.5 text-xs font-semibold text-orange-700">{{ number_format($pendingCount) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Tagihan belum lunas, diurutkan dari kunjungan terlama.</p>
                    </div>
                    <a href="{{ route('visits.index', ['status' => 'awaiting_payment']) }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">Semua tagihan <span aria-hidden="true">→</span></a>
                </div>
                @if ($pendingVisits->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($pendingVisits as $visit)
                            <a href="{{ route('visits.show', $visit) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-sm font-bold text-orange-700">{{ mb_strtoupper(mb_substr($visit->patient->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $visit->patient->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $visit->visit_number }} · {{ $visit->clinic->name }} · {{ $visit->visited_at->format('H:i') }}</span>
                                    </span>
                                </span>
                                <span class="flex items-center justify-between gap-4 sm:justify-end">
                                    <span class="text-sm font-bold tabular-nums text-slate-900">Rp {{ number_format($visit->invoice->amount, 0, ',', '.') }}</span>
                                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700">Belum lunas</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4m5 2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada tagihan yang menunggu</p>
                        <p class="mt-1 text-xs text-slate-500">Tagihan baru akan muncul setelah pelayanan farmasi selesai.</p>
                    </div>
                @endif
            </div>

            <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Pembayaran terbaru hari ini</h3>
                    <p class="mt-1 text-xs text-slate-500">Transaksi terakhir yang berhasil dicatat.</p>
                </div>
                @if ($recentPayments->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($recentPayments as $invoice)
                            <div class="flex items-start justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $invoice->visit->patient->name }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ $invoice->visit->visit_number }} · {{ $invoice->paid_at->format('H:i') }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-bold tabular-nums text-emerald-700">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('reports.payments') }}" class="flex min-h-11 items-center justify-center border-t border-slate-100 text-sm font-semibold text-clinic-700 hover:bg-slate-50">Lihat riwayat pembayaran</a>
                @else
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pembayaran yang dicatat hari ini.</p>
                @endif
            </aside>
        </section>
    </div>
@endsection
