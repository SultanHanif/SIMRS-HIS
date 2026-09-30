<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkflowDemoSeeder extends Seeder
{
    private const PRESENTATION_PASSWORD = 'password';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Akun presentasi hanya boleh dibuat pada environment local atau testing.');
        }

        $users = collect([
            'super_admin' => ['name' => 'Administrator Sistem', 'email' => 'administrator@simrs.test', 'legacy_email' => 'admin.demo@medikaflow.test', 'reset_password' => true],
            'administrasi' => ['name' => 'Petugas Front Office', 'email' => 'admin@simrs.test', 'legacy_email' => 'admisi.demo@medikaflow.test', 'reset_password' => true],
            'dokter' => ['name' => 'Arif Pratama', 'email' => 'dokter@simrs.test', 'legacy_email' => 'dokter.demo@medikaflow.test', 'reset_password' => true],
            'farmasi' => ['name' => 'Dewi Anggraini', 'email' => 'farmasi@simrs.test', 'legacy_email' => 'farmasi.demo@medikaflow.test', 'reset_password' => true],
            'kasir' => ['name' => 'Budi Santoso', 'email' => 'kasir@simrs.test', 'legacy_email' => 'kasir.demo@medikaflow.test', 'reset_password' => true],
        ])->mapWithKeys(function (array $account, string $role): array {
            $user = User::query()->where('email', $account['email'])->first();
            $legacyUser = User::query()->where('email', $account['legacy_email'])->first();

            if ($user && $user->role !== $role) {
                throw new RuntimeException("Email {$account['email']} telah digunakan oleh akun dengan peran lain.");
            }

            if (! $user) {
                $user = $legacyUser;
            }

            if (! $user) {
                $user = new User;
            }

            if ($user->exists && $user->role !== $role) {
                throw new RuntimeException("Akun {$user->email} memiliki peran yang tidak sesuai untuk akun presentasi {$role}.");
            }

            if (! $user->exists) {
                $user->fill([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'role' => $role,
                    'is_active' => true,
                ]);
                $user->email_verified_at = now();
            } elseif ($legacyUser?->is($user)) {
                $user->email = $account['email'];
            }

            if (! $user->exists || $account['reset_password']) {
                $user->password = self::PRESENTATION_PASSWORD;
            }
            $user->save();

            return [$role => $user];
        });

        $legacyAdministrator = User::query()
            ->where('email', 'admin.demo@medikaflow.test')
            ->where('name', 'Administrator Demo')
            ->where('role', 'super_admin')
            ->first();

        if ($legacyAdministrator && ! $legacyAdministrator->is($users['super_admin'])) {
            $hasReferences = Doctor::query()->where('user_id', $legacyAdministrator->id)->exists()
                || Visit::query()->where('registered_by', $legacyAdministrator->id)->exists()
                || Prescription::query()
                    ->where(fn ($query) => $query
                        ->where('prescribed_by', $legacyAdministrator->id)
                        ->orWhere('dispensed_by', $legacyAdministrator->id))
                    ->exists()
                || Invoice::query()->where('paid_by', $legacyAdministrator->id)->exists();

            if ($hasReferences) {
                throw new RuntimeException('Akun administrator presentasi lama masih terhubung ke data; lepaskan relasinya sebelum mengganti akun.');
            }

            $legacyAdministrator->delete();
        }

        $clinic = Clinic::query()->where('code', 'UJI-UMUM')->firstOrFail();
        $doctor = Doctor::query()->where('clinic_id', $clinic->id)
            ->where('name', 'dr. Arif Pratama')
            ->firstOrFail();

        if ($doctor->user_id !== null && $doctor->user_id !== $users['dokter']->id) {
            throw new RuntimeException('Profil dr. Arif Pratama telah terhubung ke akun lain; lepaskan hubungan tersebut sebelum menjalankan seeder.');
        }

        $doctor->update([
            'user_id' => $users['dokter']->id,
            'status' => 'active',
        ]);

        $patients = Patient::query()
            ->whereIn('medical_record_number', [
                'UJI-RM-2026-0001',
                'UJI-RM-2026-0002',
                'UJI-RM-2026-0003',
                'UJI-RM-2026-0004',
                'UJI-RM-2026-0005',
            ])
            ->get()
            ->keyBy('medical_record_number');

        $cases = [
            [
                'number' => 'UJI-ALUR-001',
                'patient' => 'UJI-RM-2026-0001',
                'status' => 'waiting',
                'complaint' => 'Batuk dan pilek sejak dua hari, pasien simulasi.',
            ],
            [
                'number' => 'UJI-DOKTER-001',
                'patient' => 'UJI-RM-2026-0002',
                'status' => 'in_consultation',
                'complaint' => 'Kontrol tekanan darah, pasien simulasi.',
            ],
            [
                'number' => 'UJI-FARMASI-001',
                'patient' => 'UJI-RM-2026-0003',
                'status' => 'awaiting_pharmacy',
                'complaint' => 'Demam ringan sejak kemarin, pasien simulasi.',
                'medication_name' => 'Parasetamol 500 mg (SIMULASI)',
                'prescription_status' => 'pending',
            ],
            [
                'number' => 'UJI-KASIR-001',
                'patient' => 'UJI-RM-2026-0004',
                'status' => 'awaiting_payment',
                'complaint' => 'Kontrol umum, pasien simulasi.',
                'medication_name' => 'Vitamin C 500 mg (SIMULASI)',
                'prescription_status' => 'dispensed',
            ],
            [
                'number' => 'UJI-SELESAI-001',
                'patient' => 'UJI-RM-2026-0005',
                'status' => 'completed',
                'complaint' => 'Pemeriksaan umum, pasien simulasi.',
                'medication_name' => 'Obat batuk sirup (SIMULASI)',
                'prescription_status' => 'dispensed',
                'invoice_status' => 'paid',
            ],
        ];

        foreach ($cases as $index => $case) {
            $visitedAt = now()->subMinutes(45 - ($index * 7));
            $queueDate = $visitedAt->toDateString();
            $visit = DB::transaction(function () use ($case, $clinic, $doctor, $patients, $queueDate, $users, $visitedAt): Visit {
                $existingVisit = Visit::query()
                    ->where('visit_number', $case['number'])
                    ->first();
                $queueNumber = $existingVisit?->queue_date?->toDateString() === $queueDate
                    ? $existingVisit->queue_number
                    : null;

                if ($queueNumber === null) {
                    $queueNumber = $clinic->allocateQueueNumberForDate($queueDate);
                }

                return Visit::query()->updateOrCreate(
                    ['visit_number' => $case['number']],
                    [
                        'patient_id' => $patients[$case['patient']]->id,
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $clinic->id,
                        'registered_by' => $users['administrasi']->id,
                        'visited_at' => $visitedAt,
                        'queue_date' => $queueDate,
                        'queue_number' => $queueNumber,
                        'visit_type' => 'outpatient',
                        'payment_method' => 'general',
                        'complaint' => $case['complaint'],
                        'status' => $case['status'],
                    ],
                );
            });

            if (! isset($case['medication_name'])) {
                $visit->prescription()->delete();
                $visit->invoice()->delete();
                $visit->examination()->delete();

                continue;
            }

            Examination::query()->updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'doctor_id' => $doctor->id,
                    'diagnosis_code' => null,
                    'diagnosis' => 'Kondisi simulasi.',
                    'notes' => 'Data pemeriksaan fiktif. Tidak untuk dasar keputusan klinis.',
                    'plan' => 'Lanjutkan proses ke tahap berikutnya.',
                ],
            );

            Invoice::query()->updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'amount' => $clinic->consultation_fee,
                    'status' => $case['invoice_status'] ?? 'unpaid',
                    'paid_by' => ($case['invoice_status'] ?? null) === 'paid' ? $users['kasir']->id : null,
                    'paid_at' => ($case['invoice_status'] ?? null) === 'paid' ? now()->subMinutes(5) : null,
                ],
            );

            Prescription::query()->updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'prescribed_by' => $users['dokter']->id,
                    'medication_name' => $case['medication_name'],
                    'dosage' => 'Sesuai instruksi simulasi',
                    'instructions' => 'Data resep fiktif, bukan petunjuk pengobatan.',
                    'status' => $case['prescription_status'],
                    'dispensed_by' => $case['prescription_status'] === 'dispensed' ? $users['farmasi']->id : null,
                    'dispensed_at' => $case['prescription_status'] === 'dispensed' ? now()->subMinutes(10) : null,
                ],
            );
        }
    }
}
