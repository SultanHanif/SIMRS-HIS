@extends('layouts.app')

@section('title', 'Laporan pembayaran')

@section('content')
    @php
        $startDateLabel = \Illuminate\Support\Carbon::parse($reportStartDate)->locale('id')->translatedFormat('d F Y');
        $endDateLabel = \Illuminate\Support\Carbon::parse($reportEndDate)->locale('id')->translatedFormat('d F Y');
        $reportPeriodLabel = $reportStartDate === $reportEndDate
            ? $startDateLabel
            : "{$startDateLabel} s.d. {$endDateLabel}";
    @endphp

    <div class="payment-report space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Kasir & Administrasi</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Laporan pembayaran</h2>
                <p class="mt-1 text-sm text-slate-500">Rekap transaksi lunas dan pencarian kuitansi berdasarkan pasien, nomor kunjungan, atau kasir.</p>
            </div>
            <button type="button" onclick="window.print()" class="print-hidden inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4h-2m-10-3h10v7H7v-7Z"/></svg>
                Cetak rekap
            </button>
        </section>

        <section class="print-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('reports.payments') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1.4fr_auto] xl:items-end">
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
                    <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari pembayaran</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Nama, No. RM, kunjungan, kuitansi, kasir" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                    @error('search') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 sm:col-span-2 xl:col-span-1">
                    <button class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 sm:flex-none">Tampilkan</button>
                    @if ($search !== '' || $reportStartDate !== today()->toDateString() || $reportEndDate !== today()->toDateString())
                        <a href="{{ route('reports.payments') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Hari ini</a>
                    @endif
                </div>
            </form>
        </section>

        <section aria-label="Ringkasan pembayaran" class="payment-summary grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total diterima · {{ $reportPeriodLabel }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-700">Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Transaksi lunas</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($totalTransactions) }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($payments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Waktu / kuitansi</th>
                                <th class="px-5 py-3">Pasien</th>
                                <th class="px-5 py-3">Kunjungan / poli</th>
                                <th class="px-5 py-3">Kasir</th>
                                <th class="px-5 py-3">Metode</th>
                                <th class="px-5 py-3 text-right">Jumlah</th>
                                <th class="px-5 py-3"><span class="sr-only">Kuitansi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($payments as $invoice)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-semibold text-slate-800">INV-{{ str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ $invoice->paid_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-semibold text-slate-800">{{ $invoice->visit->patient->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ $invoice->visit->patient->medical_record_number }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="text-slate-700">{{ $invoice->visit->visit_number }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ $invoice->visit->clinic->name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $invoice->paidBy?->name ?? 'Akun kasir tidak tersedia' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                        <span class="block">{{ ['cash' => 'Tunai', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer bank', 'card' => 'Kartu'][$invoice->payment_method] ?? 'Data lama' }}</span>
                                        @if ($invoice->payment_reference)
                                            <span class="mt-1 block text-xs text-slate-400">{{ $invoice->payment_reference }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-slate-900">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <a href="{{ route('visits.receipt', $invoice->visit) }}" target="_blank" rel="noopener" class="print-hidden inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">Lihat kuitansi</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="print-hidden border-t border-slate-100 px-5 py-4">{{ $payments->links() }}</div>
            @else
                <div class="px-6 py-14 text-center">
                    <h3 class="text-sm font-semibold text-slate-800">{{ $search !== '' ? 'Pembayaran tidak ditemukan' : 'Belum ada pembayaran pada periode ini' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $search !== '' ? 'Periksa kata kunci atau pilih rentang tanggal yang berbeda.' : 'Transaksi akan tercatat di sini setelah tagihan berhasil dilunasi.' }}</p>
                </div>
            @endif
        </section>
    </div>
@endsection
