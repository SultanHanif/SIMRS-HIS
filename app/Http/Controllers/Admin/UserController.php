<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return back()->with('success', 'Akun petugas berhasil dibuat.');
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $user, $data): void {
            $managedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $isActive = (bool) $data['is_active'];

            if ($managedUser->is($request->user()) && ($data['role'] !== 'super_admin' || ! $isActive)) {
                throw ValidationException::withMessages([
                    'role' => 'Akun administrator yang sedang digunakan tidak dapat dinonaktifkan atau diturunkan perannya.',
                ]);
            }

            if ($managedUser->doctorProfile()->exists() && $data['role'] !== 'dokter') {
                throw ValidationException::withMessages([
                    'role' => 'Lepaskan profil dokter dari master dokter sebelum mengubah peran akun ini.',
                ]);
            }

            $willRemainActiveAdministrator = $managedUser->role === 'super_admin'
                && $managedUser->is_active
                && ($data['role'] !== 'super_admin' || ! $isActive);

            if ($willRemainActiveAdministrator) {
                $activeAdministrators = User::query()
                    ->where('role', 'super_admin')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->count();

                if ($activeAdministrators <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'SIMRS harus memiliki minimal satu administrator aktif.',
                    ]);
                }
            }

            $managedUser->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'is_active' => $isActive,
            ]);

            if (filled($data['password'] ?? null)) {
                $managedUser->password = $data['password'];
            }

            $managedUser->save();
        });

        return back()->with('success', 'Akun petugas berhasil diperbarui.');
    }

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            'super_admin' => 'Administrator',
            'administrasi' => 'Admin / Front Office',
            'dokter' => 'Dokter',
            'farmasi' => 'Farmasi',
            'kasir' => 'Kasir',
        ];
    }
}
