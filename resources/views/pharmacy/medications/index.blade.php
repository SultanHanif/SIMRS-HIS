@extends('layouts.app')

@section('title', 'Katalog obat')

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm font-semibold text-violet-700">Master farmasi</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Katalog obat</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola daftar obat yang dapat dipilih Dokter saat membuat resep. Katalog ini belum mencatat stok.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Tambah obat</h3>
            <form method="POST" action="{{ route('pharmacy.medications.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] lg:items-end">
                @csrf
                <label class="text-sm font-semibold text-slate-700">Nama obat
                    <input name="name" value="{{ old('name') }}" required maxlength="150" placeholder="Contoh: Parasetamol" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    @error('name') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-semibold text-slate-700">Kekuatan
                    <input name="strength" value="{{ old('strength') }}" required maxlength="50" placeholder="Contoh: 500 mg" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    @error('strength') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-semibold text-slate-700">Bentuk sediaan
                    <input name="dosage_form" value="{{ old('dosage_form') }}" required maxlength="50" placeholder="Contoh: Tablet" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    @error('dosage_form') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-semibold text-slate-700">Harga satuan
                    <input name="unit_price" type="number" min="0" step="1" value="{{ old('unit_price', 0) }}" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                    @error('unit_price') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
                <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-700 px-4 text-sm font-semibold text-white hover:bg-violet-800">Tambah ke katalog</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('pharmacy.medications.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:max-w-lg">
                    <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari nama, kekuatan, atau bentuk sediaan</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Contoh: Parasetamol, 500 mg, tablet" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-100">
                </div>
                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">Cari obat</button>
                    @if ($search !== '')
                        <a href="{{ route('pharmacy.medications.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($medications->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($medications as $medication)
                        <form method="POST" action="{{ route('pharmacy.medications.update', $medication) }}" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_130px_150px_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <label class="text-xs font-semibold text-slate-500">Nama obat
                                <input name="name" value="{{ $medication->name }}" required maxlength="150" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Kekuatan
                                <input name="strength" value="{{ $medication->strength }}" required maxlength="50" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Bentuk sediaan
                                <input name="dosage_form" value="{{ $medication->dosage_form }}" required maxlength="50" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Harga satuan
                                <input name="unit_price" type="number" min="0" step="1" value="{{ $medication->unit_price }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Status
                                <select name="status" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    <option value="active" @selected($medication->status === 'active')>Aktif</option>
                                    <option value="inactive" @selected($medication->status === 'inactive')>Nonaktif</option>
                                </select>
                            </label>
                            <button class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan</button>
                            <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-5">{{ $medication->displayName() }} · {{ $medication->status === 'active' ? 'Dapat dipilih untuk resep' : 'Tidak ditawarkan pada resep baru' }}</p>
                            @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </form>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $medications->links() }}</div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-semibold text-slate-800">{{ $search !== '' ? 'Obat tidak ditemukan' : 'Katalog obat masih kosong' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $search !== '' ? 'Coba kata kunci nama, kekuatan, atau bentuk sediaan yang berbeda.' : 'Tambahkan nama, kekuatan, dan bentuk obat agar Dokter dapat memilihnya saat meresepkan.' }}</p>
                </div>
            @endif
        </section>
    </div>
@endsection
