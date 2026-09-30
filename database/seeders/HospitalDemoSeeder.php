<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use RuntimeException;

class HospitalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data rumah sakit fiktif hanya boleh dibuat pada environment local atau testing.');
        }

        $clinics = [
            ['code' => 'UJI-UMUM', 'name' => 'Poli Umum', 'consultation_fee' => 75000],
            ['code' => 'UJI-ANAK', 'name' => 'Poli Anak', 'consultation_fee' => 100000],
            ['code' => 'UJI-GIGI', 'name' => 'Poli Gigi', 'consultation_fee' => 125000],
            ['code' => 'UJI-DALAM', 'name' => 'Poli Penyakit Dalam', 'consultation_fee' => 150000],
            ['code' => 'UJI-MATA', 'name' => 'Poli Mata', 'consultation_fee' => 125000],
        ];

        $clinicRecords = [];

        foreach ($clinics as $clinic) {
            $clinicRecords[$clinic['code']] = Clinic::query()->updateOrCreate(
                ['code' => $clinic['code']],
                [
                    'name' => $clinic['name'],
                    'consultation_fee' => $clinic['consultation_fee'],
                    'status' => 'active',
                ],
            );
        }

        $doctors = [
            ['clinic' => 'UJI-UMUM', 'name' => 'dr. Arif Pratama', 'legacy_name' => 'dr. Arif Pratama (Uji Coba)', 'specialization' => 'Dokter Umum'],
            ['clinic' => 'UJI-ANAK', 'name' => 'dr. Nadia Putri', 'legacy_name' => 'dr. Nadia Putri (Uji Coba)', 'specialization' => 'Spesialis Anak'],
            ['clinic' => 'UJI-GIGI', 'name' => 'drg. Bima Santoso', 'legacy_name' => 'drg. Bima Santoso (Uji Coba)', 'specialization' => 'Dokter Gigi'],
            ['clinic' => 'UJI-DALAM', 'name' => 'dr. Rina Maharani', 'legacy_name' => 'dr. Rina Maharani (Uji Coba)', 'specialization' => 'Spesialis Penyakit Dalam'],
            ['clinic' => 'UJI-MATA', 'name' => 'dr. Fajar Nugroho', 'legacy_name' => 'dr. Fajar Nugroho (Uji Coba)', 'specialization' => 'Spesialis Mata'],
        ];

        foreach ($doctors as $doctor) {
            $doctorRecord = Doctor::query()
                ->where('clinic_id', $clinicRecords[$doctor['clinic']]->id)
                ->whereIn('name', [$doctor['name'], $doctor['legacy_name']])
                ->first();

            if ($doctorRecord) {
                $doctorRecord->update([
                    'name' => $doctor['name'],
                    'specialization' => $doctor['specialization'],
                ]);

                continue;
            }

            Doctor::query()->create([
                'user_id' => null,
                'clinic_id' => $clinicRecords[$doctor['clinic']]->id,
                'name' => $doctor['name'],
                'specialization' => $doctor['specialization'],
                'status' => 'inactive',
            ]);
        }

        $patients = [
            ['medical_record_number' => 'UJI-RM-2026-0001', 'name' => 'Alya Putri Ramadhani', 'date_of_birth' => '2003-06-14', 'sex' => 'Perempuan'],
            ['medical_record_number' => 'UJI-RM-2026-0002', 'name' => 'Bagas Aditya Pratama', 'date_of_birth' => '1988-11-02', 'sex' => 'Laki-laki'],
            ['medical_record_number' => 'UJI-RM-2026-0003', 'name' => 'Citra Maharani', 'date_of_birth' => '1994-02-21', 'sex' => 'Perempuan'],
            ['medical_record_number' => 'UJI-RM-2026-0004', 'name' => 'Dimas Saputra', 'date_of_birth' => '2012-09-03', 'sex' => 'Laki-laki'],
            ['medical_record_number' => 'UJI-RM-2026-0005', 'name' => 'Eka Lestari', 'date_of_birth' => '1976-04-18', 'sex' => 'Perempuan'],
            ['medical_record_number' => 'UJI-RM-2026-0006', 'name' => 'Farhan Akbar', 'date_of_birth' => '1967-12-27', 'sex' => 'Laki-laki'],
            ['medical_record_number' => 'UJI-RM-2026-0007', 'name' => 'Gita Permata Sari', 'date_of_birth' => '1999-08-09', 'sex' => 'Perempuan'],
            ['medical_record_number' => 'UJI-RM-2026-0008', 'name' => 'Hendra Wijaya', 'date_of_birth' => '1959-01-30', 'sex' => 'Laki-laki'],
        ];

        foreach ($patients as $patient) {
            Patient::query()->firstOrCreate(
                ['medical_record_number' => $patient['medical_record_number']],
                [
                    'national_id' => null,
                    'name' => $patient['name'],
                    'date_of_birth' => $patient['date_of_birth'],
                    'sex' => $patient['sex'],
                    'phone' => null,
                    'address' => 'Alamat simulasi, Kota Contoh',
                ],
            );
        }
    }
}
