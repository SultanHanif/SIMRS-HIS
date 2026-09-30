<?php

use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierPaymentReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationalReportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\Pharmacy\MedicationController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : app(AuthController::class)->create())
    ->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/visits', [VisitController::class, 'index'])->name('visits.index');

    Route::middleware('role:super_admin,administrasi')->group(function (): void {
        Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
        Route::get('/reports/operational/export', [OperationalReportController::class, 'export'])
            ->name('reports.operational.export');
        Route::get('/reports/operational', OperationalReportController::class)->name('reports.operational');
        Route::post('/queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next');
        Route::put('/queue/{visit}/priority', [QueueController::class, 'updatePriority'])->name('queue.priority');
        Route::post('/queue/{visit}/no-show', [QueueController::class, 'markNoShow'])->name('queue.no-show');
        Route::delete('/queue/{visit}', [QueueController::class, 'cancel'])->name('queue.cancel');

        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/possible-duplicates', [PatientController::class, 'findPossibleDuplicates'])
            ->middleware('throttle:60,1')
            ->name('patients.possible-duplicates');
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}/visits', [PatientController::class, 'visits'])->name('patients.visits');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::get('/visits/create', [VisitController::class, 'create'])->name('visits.create');
        Route::post('/visits', [VisitController::class, 'store'])->name('visits.store');
        Route::get('/visits/{visit}/ticket', [VisitController::class, 'ticket'])->name('visits.ticket');
    });

    Route::get('/reports/payments', CashierPaymentReportController::class)
        ->middleware('role:super_admin,kasir')
        ->name('reports.payments');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('/clinics', [ClinicController::class, 'index'])->name('clinics.index');
        Route::post('/clinics', [ClinicController::class, 'store'])->name('clinics.store');
        Route::put('/clinics/{clinic}', [ClinicController::class, 'update'])->name('clinics.update');

        Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
        Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
    });

    Route::prefix('pharmacy')->name('pharmacy.')->middleware('role:super_admin,farmasi')->group(function (): void {
        Route::get('/medications', [MedicationController::class, 'index'])->name('medications.index');
        Route::post('/medications', [MedicationController::class, 'store'])->name('medications.store');
        Route::put('/medications/{medication}', [MedicationController::class, 'update'])->name('medications.update');
    });

    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
    Route::get('/visits/{visit}/receipt', [VisitController::class, 'receipt'])
        ->middleware('role:super_admin,kasir')
        ->name('visits.receipt');

    Route::post('/visits/{visit}/start', [VisitController::class, 'start'])
        ->middleware('role:super_admin,dokter')
        ->name('visits.start');
    Route::post('/visits/{visit}/examination', [VisitController::class, 'examine'])
        ->middleware('role:super_admin,dokter')
        ->name('visits.examination');
    Route::post('/visits/{visit}/dispense', [VisitController::class, 'dispense'])
        ->middleware('role:super_admin,farmasi')
        ->name('visits.dispense');
    Route::post('/visits/{visit}/payment', [VisitController::class, 'pay'])
        ->middleware('role:super_admin,kasir')
        ->name('visits.payment');
});
