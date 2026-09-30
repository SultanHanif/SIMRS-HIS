<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('simrs:install')]
#[Description('Buat akun administrator pertama SIMRS')]
class CreateInitialAdmin extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('Instalasi awal sudah dilakukan karena akun pengguna telah tersedia.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Nama administrator'),
            'email' => $this->ask('Email administrator'),
            'password' => $this->secret('Kata sandi administrator (minimal 12 karakter)'),
        ];
        $data['password_confirmation'] = $this->secret('Ulangi kata sandi');

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'super_admin',
        ]);

        $this->info("Administrator {$user->email} berhasil dibuat.");
        $this->line('Masuk menggunakan akun tersebut, lalu lengkapi data poli, akun petugas, dan dokter.');

        return self::SUCCESS;
    }
}
