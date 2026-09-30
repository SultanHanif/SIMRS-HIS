@extends('layouts.app')

@section('title', 'Riwayat kunjungan pasien')

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
                <a href="{{ route('patients.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-900">← Kembali ke pasien</a>
                <p class="mt-4 text-sm font-semibold text-clinic-700">Riwayat pelayanan</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $patient->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">No. RM: {{ $patient->medical_record_number }}</p>
            </div>
            <a href="{{ route('patients.edit', $patient) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Edit data pasien</a>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-base font-bold text-slate-900">Kunjungan terdahulu</h3>
                <p class="text-xs text-slate-500">Menampilkan informasi operasional kunjungan; catatan klinis tidak ditampilkan di halaman ini.</p>
            </div>

            @if ($visits->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal & antrean</th>
                                <th class="px-5 py-3">Poli</th>
                                <th class="px-5 py-3">Dokter</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"><span class="sr-only">Detail</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($visits as $visit)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-semibold text-slate-800">{{ $visit->visited_at->format('d/m/Y H:i') }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">Antrean {{ $visit->formattedQueueNumber() }} · {{ $visit->clinic->code }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">No. kunjungan: {{ $visit->visit_number }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $visit->clinic->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-medium text-slate-700">{{ $visit->doctor->name }}</span>
                                        @if ($visit->doctor->specialization)
                                            <span class="mt-1 block text-xs text-slate-400">{{ $visit->doctor->specialization }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$visit->status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10' }}">{{ $statusLabels[$visit->status] ?? $visit->status }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <a href="{{ route('visits.show', $visit) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-clinic-200 hover:bg-clinic-50 hover:text-clinic-800">Detail</a>
                                    </td>
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
                    <h3 class="mt-3 text-sm font-semibold text-slate-800">Belum ada riwayat kunjungan</h3>
                    <p class="mt-1 text-sm text-slate-500">Kunjungan pasien berikutnya akan tercatat di sini.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
