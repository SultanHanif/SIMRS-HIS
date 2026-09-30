<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SIMRS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    @php
        $user = auth()->user();
        $roleLabel = [
            'super_admin' => 'Administrator',
            'administrasi' => 'Admin / Front Office',
            'dokter' => 'Dokter',
            'farmasi' => 'Farmasi',
            'kasir' => 'Kasir',
        ][$user->role] ?? 'Petugas';
    @endphp

    <div class="min-h-screen lg:flex">
        <div id="sidebar-overlay" class="app-chrome fixed inset-0 z-40 hidden bg-slate-950/40 backdrop-blur-sm lg:hidden" data-sidebar-close></div>

        <aside id="sidebar" class="app-chrome fixed inset-y-0 left-0 z-50 flex w-[276px] -translate-x-full flex-col border-r border-slate-200/80 bg-white/95 shadow-2xl shadow-slate-900/5 backdrop-blur-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none">
            <div class="flex h-[78px] items-center gap-3 border-b border-slate-100 px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-clinic-700 text-white shadow-lg shadow-clinic-900/15">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-lg font-bold tracking-tight text-slate-900">SIMRS</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[.17em] text-slate-400">SIMRS Rawat Jalan</span>
                    </span>
                </a>
                <button type="button" class="ml-auto rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Tutup navigasi" data-sidebar-close>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </div>

            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sm font-bold text-clinic-700 shadow-sm ring-1 ring-slate-200">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $user->name }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $roleLabel }}</span>
                    </span>
                    <span class="ml-auto h-2 w-2 rounded-full bg-emerald-500 ring-4 ring-emerald-100" title="Sesi aktif"></span>
                </div>
            </div>

            <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-4 py-5">
                <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.16em] text-slate-400">Workspace</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                        'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('dashboard'),
                        'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('dashboard'),
                    ])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="10" width="8" height="11" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('visits.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                        'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('visits.*'),
                        'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('visits.*'),
                    ])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h6l5 5v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h2Zm6 0v5h5M8 13h8M8 17h6"/></svg>
                        Kunjungan & antrean
                    </a>

                    @if ($user->hasRole('super_admin', 'administrasi'))
                        <a href="{{ route('queue.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('queue.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('queue.*'),
                        ])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7"/><circle cx="18" cy="12" r="2"/><circle cx="15" cy="18" r="2"/></svg>
                            Manajemen antrean
                        </a>
                        <a href="{{ route('reports.operational') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('reports.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('reports.*'),
                        ])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h17M8 15v-3m4 3V7m4 8v-5m4 5V4"/></svg>
                            Laporan operasional
                        </a>
                        <a href="{{ route('patients.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('patients.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('patients.*'),
                        ])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-4v6m3-3h-6"/></svg>
                            Data pasien
                        </a>
                    @endif

                    @if ($user->hasRole('super_admin', 'kasir'))
                        <a href="{{ route('reports.payments') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('reports.payments'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('reports.payments'),
                        ])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M3 10h18m-13 5h3"/></svg>
                            Laporan pembayaran
                        </a>
                    @endif

                    @if ($user->hasRole('super_admin'))
                        <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[.16em] text-slate-400">Pengaturan</p>
                        <a href="{{ route('admin.users.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('admin.users.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.users.*'),
                        ])>Akun & peran</a>
                        <a href="{{ route('admin.clinics.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('admin.clinics.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.clinics.*'),
                        ])>Poli & tarif</a>
                        <a href="{{ route('admin.doctors.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('admin.doctors.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.doctors.*'),
                        ])>Profil dokter</a>
                    @endif
                    @if ($user->hasRole('super_admin', 'farmasi'))
                        <a href="{{ route('pharmacy.medications.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'nav-link-active bg-clinic-50 text-clinic-800' => request()->routeIs('pharmacy.medications.*'),
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('pharmacy.medications.*'),
                        ])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8a5 5 0 0 1 0 10H8A5 5 0 0 1 8 3Zm0 0v18m8-8v8"/></svg>
                            Katalog obat
                        </a>
                    @endif
                </div>

                <div class="mt-8 rounded-2xl border border-clinic-100 bg-gradient-to-br from-clinic-50 to-white p-4">
                    <div class="flex items-center gap-2 text-clinic-800">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                        </span>
                        <span class="text-xs font-bold">Alur pelayanan</span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-600">Admin / Front Office → Antrean → Pemeriksaan → Farmasi → Pembayaran</p>
                </div>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>
                        Keluar dari sistem
                    </button>
                </form>
            </div>
        </aside>

        <div class="app-content min-w-0 flex-1 lg:pl-[276px]">
            <header class="app-chrome sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
                <div class="flex h-[70px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button id="sidebar-open" type="button" class="rounded-xl p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka navigasi">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0">
                            <p class="hidden text-[11px] font-semibold uppercase tracking-[.14em] text-slate-400 sm:block">Sistem Informasi Manajemen Rumah Sakit</p>
                            <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">@yield('title', 'Dashboard')</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:inline-flex">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Sistem beroperasi
                        </span>
                        @if ($user->hasRole('super_admin', 'administrasi'))
                            <a href="{{ route('visits.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-clinic-700 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-clinic-800 sm:px-4">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                                <span class="hidden sm:inline">Daftar kunjungan</span>
                                <span class="sm:hidden">Daftar</span>
                            </a>
                        @endif
                    </div>
                </div>
            </header>

            <main class="app-main mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
                @if (session('success'))
                    <div role="status" class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div role="status" class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18.5A2 2 0 0 0 3.55 21h16.9a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.46 0Z"/></svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="app-chrome border-t border-slate-200/80 px-4 py-5 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                SIMRS Rawat Jalan · {{ now()->year }}
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
