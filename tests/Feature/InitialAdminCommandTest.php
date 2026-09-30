<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_creates_the_first_administrator_and_hashes_the_password(): void
    {
        $password = 'HospitalSecurePass123!';

        $this->artisan('simrs:install')
            ->expectsQuestion('Nama administrator', 'SIMRS Administrator')
            ->expectsQuestion('Email administrator', 'admin@hospital.test')
            ->expectsQuestion('Kata sandi administrator (minimal 12 karakter)', $password)
            ->expectsQuestion('Ulangi kata sandi', $password)
            ->expectsOutputToContain('Administrator admin@hospital.test berhasil dibuat.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@hospital.test',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $this->assertSame(true, Hash::check($password, User::query()->where('email', 'admin@hospital.test')->value('password')));
    }

    public function test_installer_refuses_to_run_after_the_first_account_exists(): void
    {
        User::factory()->create(['role' => 'super_admin']);

        $this->artisan('simrs:install')
            ->expectsOutputToContain('Instalasi awal sudah dilakukan')
            ->assertFailed();
    }
}
