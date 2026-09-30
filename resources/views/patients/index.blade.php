@extends('layouts.app')

@section('title', 'Data pasien')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-clinic-700">Master data</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data pasien</h2>
                <p class="mt-1 text-sm text-slate-500">Cari dan kelola identitas pasien dengan rekam medis yang terhubung.</p>
            </div>
            <a href="{{ route('patients.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-clinic-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-clinic-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                Daftarkan pasien
            </a>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('patients.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:p-5">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">Cari pasien</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg>
                    <input name="search" value="{{ $search }}" placeholder="Cari nama, nomor rekam medis, atau NIK" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm outline-none transition focus:border-clinic-500 focus:bg-white focus:ring-4 focus:ring-clinic-100">
                </label>
                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
                    @if ($search !== '')
                        <a href="{{ route('patients.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>

            @if ($patients->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr><th class="px-5 py-3">Pasien</th><th class="px-5 py-3">Nomor rekam medis</th><th class="px-5 py-3">Tanggal lahir</th><th class="px-5 py-3">Jenis kelamin</th><th class="px-5 py-3">Kontak</th><th class="px-5 py-3">Terdaftar</th><th class="px-5 py-3"><span class="sr-only">Tindakan</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($patients as $patient)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-clinic-50 text-xs font-bold text-clinic-800">{{ mb_strtoupper(mb_substr($patient->name, 0, 1)) }}</span>
                                            <span><span class="block font-semibold text-slate-800">{{ $patient->name }}</span><span class="mt-0.5 block text-xs text-slate-400">{{ $patient->national_id ?: 'NIK belum dicatat' }}</span></span>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-700">{{ $patient->medical_record_number }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $patient->sex }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $patient->phone ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $patient->created_at->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('patients.visits', $patient) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-clinic-200 hover:bg-clinic-50 hover:text-clinic-800">Riwayat</a>
                                            <a href="{{ route('patients.edit', $patient) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-clinic-200 hover:bg-clinic-50 hover:text-clinic-800">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $patients->links() }}</div>
            @else
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 21a8 8 0 0 1 16 0m1-13v6m3-3h-6"/></svg>
                    </span>
                    <h3 class="mt-3 text-sm font-semibold text-slate-800">{{ $search ? 'Pasien tidak ditemukan' : 'Belum ada data pasien' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Coba periksa ejaan atau gunakan nomor rekam medis.' : 'Tambahkan identitas pasien sebelum membuat kunjungan.' }}</p>
                    @if (! $search)
                        <a href="{{ route('patients.create') }}" class="mt-4 inline-flex min-h-10 items-center rounded-xl bg-clinic-700 px-4 text-sm font-semibold text-white hover:bg-clinic-800">Daftarkan pasien</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
