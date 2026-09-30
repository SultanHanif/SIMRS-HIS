@extends('layouts.app')

@section('title', 'Laporan operasional')

@section('content')
    @php
        $startDateLabel = \Illuminate\Support\Carbon::parse($reportStartDate)->locale('id')->translatedFormat('d F Y');
        $endDateLabel = \Illuminate\Support\Carbon::parse($reportEndDate)->locale('id')->translatedFormat('d F Y');
        $reportPeriodLabel = $reportStartDate === $reportEndDate
            ? $startDateLabel
            : "{$startDateLabel} s.d. {$endDateLabel}";
        $today = today();
        $quickPeriods = [
            [
                'label' => '7 hari terakhir',
                'start' => $today->copy()->subDays(6)->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'label' => 'Bulan ini',
                'start' => $today->copy()->startOfMonth()->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'label' => 'Bulan lalu',
                'start' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'end' => $today->copy()->startOfMonth()->subDay()->toDateString(),
            ],
        ];
        $summaryCards = [
            ['Total kunjungan', $totals['visits'], 'bg-blue-50 text-blue-700'],
            ['Menunggu', $totals['waiting'], 'bg-amber-50 text-amber-700'],
            ['Dalam layanan', $totals['in_service'], 'bg-violet-50 text-violet-700'],
            ['Selesai', $totals['completed'], 'bg-emerald-50 text-emerald-700'],
            ['Tidak hadir', $totals['no_show'], 'bg-rose-50 text-rose-700'],
            ['Dibatalkan', $totals['cancelled'], 'bg-slate-100 text-slate-700'],
        ];
    @endphp

    <div class="operational-report space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-clinic-700">Administrasi & Front Office</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Laporan operasional</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan kunjungan dan status antrean tanpa rincian klinis pasien.</p>
            </div>
            <button type="button" onclick="window.print()" class="print-hidden inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-3h10v7H7v-7Z"/><path stroke-linecap="round" d="M17 12h.01"/></svg>
                Cetak laporan
            </button>
        </section>

        <section class="print-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('reports.operational') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end">
                <div>
                    <label for="date_from" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari tanggal</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $reportStartDate }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('date_from') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_to" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai tanggal</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $reportEndDate }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('date_to') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="clinic_id" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Poli / unit layanan</label>
                    <select id="clinic_id" name="clinic_id" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Semua poli</option>
                        @foreach ($availableClinics as $clinic)
                            <option value="{{ $clinic->id }}" @selected((string) $clinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
                        @endforeach
                    </select>
                    @error('clinic_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 sm:col-span-2 xl:col-span-1">
                    <button class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 sm:flex-none">Tampilkan</button>
                    <a href="{{ route('reports.operational.export', ['date_from' => $reportStartDate, 'date_to' => $reportEndDate, 'clinic_id' => $clinicId]) }}" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl border border-clinic-200 bg-clinic-50 px-4 text-sm font-semibold text-clinic-800 transition hover:bg-clinic-100 sm:flex-none">Ekspor CSV</a>
                    @if ($clinicId !== null || $reportStartDate !== today()->toDateString() || $reportEndDate !== today()->toDateString())
                        <a href="{{ route('reports.operational') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Hari ini</a>
                    @endif
                </div>
            </form>
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                <span class="mr-1 text-xs font-semibold text-slate-500">Periode cepat:</span>
                @foreach ($quickPeriods as $period)
                    @php
                        $isActivePeriod = $reportStartDate === $period['start'] && $reportEndDate === $period['end'];
                    @endphp
                    <a
                        href="{{ route('reports.operational', ['date_from' => $period['start'], 'date_to' => $period['end'], 'clinic_id' => $clinicId]) }}"
                        @if ($isActivePeriod) aria-current="date" @endif
                        @class([
                            'rounded-lg border px-3 py-2 text-xs font-semibold transition',
                            'border-clinic-300 bg-clinic-50 text-clinic-800 ring-1 ring-clinic-200' => $isActivePeriod,
                            'border-slate-200 text-slate-600 hover:border-clinic-200 hover:bg-clinic-50 hover:text-clinic-800' => ! $isActivePeriod,
                        ])
                    >{{ $period['label'] }}</a>
                @endforeach
            </div>
        </section>

        <section aria-label="Ringkasan laporan" class="print-summary grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($summaryCards as [$label, $count, $style])
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                        <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $style }}">{{ number_format($count) }}</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ $reportPeriodLabel }}</p>
                </article>
            @endforeach
        </section>

        <section class="print-table overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-base font-bold text-slate-900">Rincian per poli</h3>
                <p class="text-xs text-slate-500">Menunggu mencakup pasien yang sudah dan belum dipanggil; status tidak mengubah jumlah kunjungan.</p>
            </div>

            @if ($clinics->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Poli</th>
                                <th class="px-5 py-3 text-right">Total</th>
                                <th class="px-5 py-3 text-right">Menunggu</th>
                                <th class="px-5 py-3 text-right">Belum dipanggil</th>
                                <th class="px-5 py-3 text-right">Dalam layanan</th>
                                <th class="px-5 py-3 text-right">Selesai</th>
                                <th class="px-5 py-3 text-right">Tidak hadir</th>
                                <th class="px-5 py-3 text-right">Dibatalkan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($clinics as $clinic)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-semibold text-slate-800">{{ $clinic['name'] }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ $clinic['code'] }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-bold tabular-nums text-slate-800">{{ number_format($clinic['total']) }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums text-amber-700">{{ number_format($clinic['waiting']) }} <span class="block text-xs text-slate-400">{{ $clinic['called'] }} dipanggil</span></td>
                                    <td class="px-5 py-4 text-right tabular-nums">{{ number_format($clinic['uncalled']) }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums text-violet-700">{{ number_format($clinic['in_service']) }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums text-emerald-700">{{ number_format($clinic['completed']) }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums text-rose-700">{{ number_format($clinic['no_show']) }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums text-slate-600">{{ number_format($clinic['cancelled']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-bold text-slate-800">
                            <tr>
                                <th class="px-5 py-4">Total</th>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['visits']) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['waiting']) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($clinics->sum('uncalled')) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['in_service']) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['completed']) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['no_show']) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">{{ number_format($totals['cancelled']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <h3 class="text-sm font-semibold text-slate-800">Belum ada data poli</h3>
                    <p class="mt-1 text-sm text-slate-500">Tambahkan poli terlebih dahulu untuk melihat laporan.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
