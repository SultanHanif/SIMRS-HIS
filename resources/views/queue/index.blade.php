@extends('layouts.app')

@section('title', 'Manajemen antrean')

@section('content')
    @php
        $queueStatuses = [
            'waiting' => ['Menunggu', 'bg-amber-50 text-amber-700 ring-amber-600/15'],
            'in_consultation' => ['Dalam pemeriksaan', 'bg-blue-50 text-blue-700 ring-blue-600/15'],
            'awaiting_pharmacy' => ['Menunggu farmasi', 'bg-violet-50 text-violet-700 ring-violet-600/15'],
            'awaiting_payment' => ['Menunggu pembayaran', 'bg-orange-50 text-orange-700 ring-orange-600/15'],
            'completed' => ['Selesai', 'bg-emerald-50 text-emerald-700 ring-emerald-600/15'],
            'no_show' => ['Tidak hadir', 'bg-rose-50 text-rose-700 ring-rose-600/15'],
            'cancelled' => ['Dibatalkan', 'bg-slate-100 text-slate-600 ring-slate-500/15'],
        ];
        $selectedClinic = $clinics->firstWhere('id', $clinicId);
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-clinic-700">Operasional Front Office</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Manajemen antrean</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola antrean hari ini per poli, panggil pasien, dan perbarui status kehadiran.</p>
            </div>
            <a href="{{ route('visits.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-clinic-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-clinic-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                Daftar kunjungan
            </a>
        </section>

        @if ($clinics->isEmpty())
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                <h3 class="font-semibold">Belum ada poli aktif</h3>
                <p class="mt-1">Minta administrator mengaktifkan poli sebelum antrean dapat dikelola.</p>
            </section>
        @else
            <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between sm:p-5">
                <form method="GET" action="{{ route('queue.index') }}" class="flex w-full flex-col gap-3 sm:max-w-md sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="clinic_id" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Poli / unit layanan</label>
                        <select id="clinic_id" name="clinic_id" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-clinic-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-clinic-100">
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected((int) $clinicId === $clinic->id)>{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Tampilkan antrean</button>
                </form>

                @if ($clinicId)
                    <form method="POST" action="{{ route('queue.call-next') }}">
                        @csrf
                        <input type="hidden" name="clinic_id" value="{{ $clinicId }}">
                        <button @disabled($uncalledCount === 0) class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            Panggil berikutnya
                        </button>
                    </form>
                @endif
            </section>

            @if ($selectedClinic)
                <section aria-label="Ringkasan antrean" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['Menunggu', $statusCounts['waiting'] ?? 0, 'bg-amber-50 text-amber-700'],
                        ['Sudah dipanggil', $calledCount, 'bg-blue-50 text-blue-700'],
                        ['Tidak hadir', $statusCounts['no_show'] ?? 0, 'bg-rose-50 text-rose-700'],
                        ['Dibatalkan', $statusCounts['cancelled'] ?? 0, 'bg-slate-100 text-slate-700'],
                    ] as [$label, $count, $style])
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $style }}">{{ number_format($count) }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-400">{{ $selectedClinic->name }} · {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
                        </article>
                    @endforeach
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Antrean {{ $selectedClinic->name }}</h3>
                            <p class="mt-1 text-xs text-slate-500">Pasien didahulukan tampil sebelum antrean normal; urutan berikutnya mengikuti waktu pendaftaran.</p>
                        </div>
                        <span class="text-xs font-medium text-slate-400">{{ $visits->total() }} kunjungan hari ini</span>
                    </div>

                    @if ($visits->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-left">
                                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">No. antrean</th>
                                        <th class="px-5 py-3">Pasien</th>
                                        <th class="px-5 py-3">Dokter</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Prioritas</th>
                                        <th class="px-5 py-3">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    @foreach ($visits as $visit)
                                        @php
                                            [$statusLabel, $statusStyle] = $queueStatuses[$visit->status] ?? [$visit->status, 'bg-slate-100 text-slate-600 ring-slate-500/10'];
                                        @endphp
                                        <tr class="transition hover:bg-slate-50/70">
                                            <td class="whitespace-nowrap px-5 py-4">
                                                <span class="font-semibold text-slate-800">{{ $visit->formattedQueueNumber() }}</span>
                                                <span class="mt-1 block text-xs text-slate-400">{{ $visit->visited_at->format('H:i') }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                <a href="{{ route('visits.show', $visit) }}" class="font-semibold text-slate-800 hover:text-clinic-700">{{ $visit->patient->name }}</a>
                                                <span class="mt-1 block text-xs text-slate-400">{{ $visit->patient->medical_record_number }} · {{ $visit->clinic->code }} · {{ $visit->visit_number }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $visit->doctor->name }}</td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyle }}">{{ $statusLabel }}</span>
                                                @if ($visit->called_at && $visit->status === 'waiting')
                                                    <span class="mt-1 block text-xs font-medium text-blue-700">Dipanggil {{ $visit->called_at->format('H:i') }}</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                @if ($visit->status === 'waiting')
                                                    <form method="POST" action="{{ route('queue.priority', $visit) }}" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <select name="queue_priority" aria-label="Prioritas {{ $visit->patient->name }}" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-100">
                                                            <option value="normal" @selected($visit->queue_priority === 'normal')>Normal</option>
                                                            <option value="priority" @selected($visit->queue_priority === 'priority')>Didahulukan</option>
                                                        </select>
                                                        <button class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Simpan</button>
                                                    </form>
                                                @else
                                                    <span class="text-xs font-medium text-slate-500">{{ $visit->queue_priority === 'priority' ? 'Didahulukan' : 'Normal' }}</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                @if ($visit->status === 'waiting')
                                                    <div class="flex items-center gap-2">
                                                        <form method="POST" action="{{ route('queue.no-show', $visit) }}" onsubmit="return confirm('Tandai pasien ini tidak hadir?')">
                                                            @csrf
                                                            <button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Tidak hadir</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('queue.cancel', $visit) }}" onsubmit="return confirm('Batalkan kunjungan ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="rounded-lg px-2 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">Batal</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-slate-400">Tidak ada tindakan</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-100 px-5 py-4">{{ $visits->links() }}</div>
                    @else
                        <div class="px-6 py-14 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-clinic-50 text-clinic-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16m-15-6h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
                            </span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">Belum ada antrean hari ini</h3>
                            <p class="mt-1 text-sm text-slate-500">Kunjungan baru ke poli ini akan tampil di sini.</p>
                        </div>
                    @endif
                </section>

                <p class="text-xs leading-5 text-slate-400">Prioritas di halaman ini hanya mengatur urutan operasional dan bukan penilaian triase medis. Pemanggilan dicatat, sementara status kunjungan tetap Menunggu hingga dokter memulai pemeriksaan.</p>
            @endif
        @endif
    </div>
@endsection
