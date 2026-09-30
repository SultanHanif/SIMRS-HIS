@extends('layouts.app')

@section('title', 'Akun & peran')

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm font-semibold text-clinic-700">Pengaturan akses</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Akun petugas</h2>
            <p class="mt-1 text-sm text-slate-500">Buat akun kerja dan berikan hak akses sesuai tanggung jawab pelayanan.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Tambah akun</h3>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <label class="text-sm font-semibold text-slate-700">Nama petugas
                    <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700">Email kerja
                    <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="off" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700">Peran
                    <select name="role" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                        @foreach ($roles as $role => $label)
                            <option value="{{ $role }}" @selected(old('role') === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700">Kata sandi awal
                    <input name="password" type="password" required minlength="12" autocomplete="new-password" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700">Konfirmasi kata sandi
                    <input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-normal">
                </label>
                <div class="flex items-end">
                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-clinic-700 px-4 text-sm font-semibold text-white hover:bg-clinic-800 sm:w-auto">Buat akun</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Akun terdaftar <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $users->total() }}</span></h3>
            </div>
            @if ($users->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($users as $managedUser)
                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="grid gap-3 p-5 lg:grid-cols-[1fr_1fr_180px_130px_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <label class="text-xs font-semibold text-slate-500">Nama
                                <input name="name" value="{{ $managedUser->name }}" required maxlength="255" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Email kerja
                                <input name="email" type="email" value="{{ $managedUser->email }}" required maxlength="255" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Peran
                                <select name="role" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    @foreach ($roles as $role => $label)
                                        <option value="{{ $role }}" @selected($managedUser->role === $role)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-500">Status
                                <select name="is_active" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal text-slate-800">
                                    <option value="1" @selected($managedUser->is_active)>Aktif</option>
                                    <option value="0" @selected(! $managedUser->is_active)>Nonaktif</option>
                                </select>
                            </label>
                            <button class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan</button>
                            <label class="text-xs font-semibold text-slate-500 lg:col-span-2">Kata sandi baru (opsional)
                                <input name="password" type="password" minlength="12" autocomplete="new-password" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                            </label>
                            <label class="text-xs font-semibold text-slate-500 lg:col-span-2">Konfirmasi kata sandi baru
                                <input name="password_confirmation" type="password" minlength="12" autocomplete="new-password" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                            </label>
                        </form>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
            @else
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada akun petugas.</p>
            @endif
        </section>
    </div>
@endsection
