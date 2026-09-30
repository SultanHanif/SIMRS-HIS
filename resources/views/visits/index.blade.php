@extends('layouts.app')

@section('title', 'Kunjungan & antrean')

@section('content')
    @php
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
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-clinic-700">Pelayanan rawat jalan</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Kunjungan & antrean</h2>
                <p class="mt-1 text-sm text-slate-500">Pantau perjalanan kunjungan pasien dari pendaftaran hingga selesai.</p>
            </div>
            @if (auth()->user()->hasRole('super_admin', 'administrasi'))
                <a href="{{ route('visits.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-clinic-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-clinic-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                    Daftar kunjungan
                </a>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('visits.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-end sm:justify-between sm:p-5">
                <div class="w-full sm:max-w-xs">
                    <label for="status" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Filter status</label>
                    <select id="status" name="status" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Terapkan</button>
                    @if ($status !== '')
                        <a href="{{ route('visits.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>

            @if ($visits->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr><th class="px-5 py-3">Antrean / waktu</th><th class="px-5 py-3">Pasien</th><th class="px-5 py-3">Poli & dokter</th><th class="px-5 py-3">Penjamin</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Detail</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($visits as $visit)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4"><span class="font-semibold text-slate-800">{{ $visit->formattedQueueNumber() }} · {{ $visit->clinic->code }}</span><span class="mt-1 block text-xs text-slate-400">{{ $visit->visited_at->format('d/m/Y H:i') }} · {{ $visit->visit_number }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="font-semibold text-slate-800">{{ $visit->patient->name }}</span><span class="mt-1 block text-xs text-slate-400">{{ $visit->patient->medical_record_number }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="text-slate-700">{{ $visit->clinic->name }}</span><span class="mt-1 block text-xs text-slate-400">{{ $visit->doctor->name }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ ['general' => 'Umum', 'bpjs' => 'BPJS', 'insurance' => 'Asuransi'][$visit->payment_method] ?? $visit->payment_method }}</td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$visit->status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10' }}">{{ $statusLabels[$visit->status] ?? $visit->status }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4"><a href="{{ route('visits.show', $visit) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:border-clinic-200 hover:bg-clinic-50 hover:text-clinic-800">Buka</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $visits->links() }}</div>
            @else
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16m-15-6h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                    <h3 class="mt-3 text-sm font-semibold text-slate-800">Belum ada kunjungan</h3>
                    <p class="mt-1 text-sm text-slate-500">Pendaftaran baru akan muncul di worklist ini.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
