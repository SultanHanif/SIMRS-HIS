<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · SIMRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(420px,.9fr)]">
        <section class="relative isolate hidden overflow-hidden bg-slate-950 px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_20%,rgba(32,163,134,.38),transparent_32%),linear-gradient(145deg,#071b22,#102f36_55%,#12695a)]"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-36 top-20 -z-10 h-[34rem] w-[34rem] rounded-full border border-white/10"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-20 top-36 -z-10 h-[25rem] w-[25rem] rounded-full border border-white/10"></div>
            <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"/></svg>
                </span>
                <span>
                    <span class="block text-xl font-bold tracking-tight">SIMRS</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[.2em] text-teal-100/70">Sistem Informasi Manajemen Rumah Sakit</span>
                </span>
            </a>

            <div class="max-w-2xl py-16">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-teal-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-300"></span>
                    Alur pelayanan terhubung
                </span>
                <h1 class="mt-6 text-5xl font-semibold leading-[1.12] tracking-tight xl:text-6xl">
                    Pelayanan yang lebih teratur, <span class="text-teal-200">dimulai di sini.</span>
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-slate-200/80">
                    Satu ruang kerja untuk admisi, dokter, farmasi, dan kasir mengikuti perjalanan kunjungan pasien.
                </p>

                <div class="mt-10 grid max-w-xl grid-cols-2 gap-3">
                    @foreach (['Admin / Front Office & antrean', 'Pemeriksaan klinis', 'Resep & farmasi', 'Tagihan & pembayaran'] as $step)
                        <div class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm text-white/85">
                            <svg class="h-4 w-4 shrink-0 text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                            {{ $step }}
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="text-xs text-white/45">SIMRS rawat jalan · Akses khusus petugas</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-10 inline-flex items-center gap-2 lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-clinic-700 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"/></svg>
                    </span>
                    <span class="text-lg font-bold text-slate-900">SIMRS</span>
                </a>

                <div class="mb-8">
                    <p class="text-sm font-semibold text-clinic-700">{{ $hasUsers ? 'Selamat datang kembali' : 'Penyiapan awal' }}</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ $hasUsers ? 'Masuk ke SIMRS' : 'Buat administrator pertama' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        {{ $hasUsers ? 'Gunakan akun petugas yang diberikan administrator rumah sakit.' : 'Jalankan php artisan simrs:install pada terminal server untuk membuat akun administrator dengan aman.' }}
                    </p>
                </div>

                @if ($hasUsers)
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email kerja</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="nama@rumahsakit.id" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        @error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-slate-700">Kata sandi</label>
                            <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false" class="rounded-md text-xs font-semibold text-clinic-700 hover:text-clinic-900 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2">
                                Tampilkan
                            </button>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-clinic-500 focus:outline-none focus:ring-4 focus:ring-clinic-100">
                        @error('password') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-clinic-700 focus:ring-clinic-500">
                        Ingat sesi pada perangkat ini
                    </label>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-clinic-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-clinic-900/15 transition hover:bg-clinic-800">
                        Masuk ke workspace
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                </form>

                @else
                    <div class="mt-7 rounded-2xl border border-clinic-100 bg-clinic-50 p-4 text-sm leading-6 text-clinic-900">
                        Akun bawaan dan data pasien tidak dibuat otomatis. Administrator rumah sakit membuat akun pertama dari terminal server.
                    </div>
                @endif

                <p class="mt-8 text-center text-xs leading-5 text-slate-400">Akses data pasien dibatasi berdasarkan peran dan kebutuhan pelayanan.</p>
            </div>
        </section>
    </main>
</body>
</html>
