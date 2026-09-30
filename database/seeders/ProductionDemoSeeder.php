<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Seeder ini membuat dataset operasional baru.
         *
         * Data user TIDAK dibuat, diubah, atau dihapus.
         * Data master lama juga TIDAK digunakan.
         */

        $administrationUser = DB::table('users')
            ->where('email', 'admin@simrs.test')
            ->where('role', 'administrasi')
            ->first();

        $doctorUser = DB::table('users')
            ->where('email', 'dokter@simrs.test')
            ->where('role', 'dokter')
            ->first();

        $pharmacyUser = DB::table('users')
            ->where('email', 'farmasi@simrs.test')
            ->where('role', 'farmasi')
            ->first();

        $cashierUser = DB::table('users')
            ->where('email', 'kasir@simrs.test')
            ->where('role', 'kasir')
            ->first();

        if (! $administrationUser) {
            throw new RuntimeException('Akun administrasi tidak ditemukan.');
        }

        if (! $doctorUser) {
            throw new RuntimeException('Akun dokter tidak ditemukan.');
        }

        if (! $pharmacyUser) {
            throw new RuntimeException('Akun farmasi tidak ditemukan.');
        }

        if (! $cashierUser) {
            throw new RuntimeException('Akun kasir tidak ditemukan.');
        }

        DB::transaction(function () use (
            $administrationUser,
            $doctorUser,
            $pharmacyUser,
            $cashierUser
        ): void {

            /*
             * ============================================================
             * 1. POLI
             * ============================================================
             */

            $clinics = [
                [
                    'code' => 'POL-UM',
                    'name' => 'Poli Umum',
                    'consultation_fee' => 75000,
                ],
                [
                    'code' => 'POL-ANA',
                    'name' => 'Poli Anak',
                    'consultation_fee' => 100000,
                ],
                [
                    'code' => 'POL-PD',
                    'name' => 'Poli Penyakit Dalam',
                    'consultation_fee' => 150000,
                ],
                [
                    'code' => 'POL-GIG',
                    'name' => 'Poli Gigi',
                    'consultation_fee' => 125000,
                ],
                [
                    'code' => 'POL-MAT',
                    'name' => 'Poli Mata',
                    'consultation_fee' => 125000,
                ],
            ];

            $clinicIds = [];

            foreach ($clinics as $clinic) {
                $existing = DB::table('clinics')
                    ->where('code', $clinic['code'])
                    ->first();

                if ($existing) {
                    $clinicIds[$clinic['code']] = $existing->id;
                    continue;
                }

                $clinicIds[$clinic['code']] = DB::table('clinics')->insertGetId([
                    'code' => $clinic['code'],
                    'name' => $clinic['name'],
                    'consultation_fee' => $clinic['consultation_fee'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
             * ============================================================
             * 2. DOKTER
             * ============================================================
             */

            $doctors = [
                [
                    'clinic' => 'POL-UM',
                    'name' => 'dr. Arif Pratama',
                    'specialization' => 'Dokter Umum',
                    'user_id' => $doctorUser->id,
                ],
                [
                    'clinic' => 'POL-ANA',
                    'name' => 'dr. Nadia Permatasari',
                    'specialization' => 'Spesialis Anak',
                    'user_id' => null,
                ],
                [
                    'clinic' => 'POL-PD',
                    'name' => 'dr. Rina Maharani',
                    'specialization' => 'Spesialis Penyakit Dalam',
                    'user_id' => null,
                ],
                [
                    'clinic' => 'POL-GIG',
                    'name' => 'drg. Bima Santoso',
                    'specialization' => 'Dokter Gigi',
                    'user_id' => null,
                ],
                [
                    'clinic' => 'POL-MAT',
                    'name' => 'dr. Fajar Nugraha',
                    'specialization' => 'Spesialis Mata',
                    'user_id' => null,
                ],
            ];

            $doctorIds = [];

            foreach ($doctors as $doctor) {
                $existing = DB::table('doctors')
                    ->where('clinic_id', $clinicIds[$doctor['clinic']])
                    ->where('name', $doctor['name'])
                    ->first();

                if ($existing) {
                    $doctorIds[$doctor['clinic']] = $existing->id;
                    continue;
                }

                $doctorIds[$doctor['clinic']] = DB::table('doctors')->insertGetId([
                    'user_id' => $doctor['user_id'],
                    'clinic_id' => $clinicIds[$doctor['clinic']],
                    'name' => $doctor['name'],
                    'specialization' => $doctor['specialization'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
             * ============================================================
             * 3. PASIEN
             * ============================================================
             */

            $patients = [
                [
                    'medical_record_number' => 'RM-2026-0101',
                    'national_id' => null,
                    'name' => 'Muhammad Rizky Maulana',
                    'date_of_birth' => '1997-03-12',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560101',
                    'address' => 'Jl. Setia Budi No. 18, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0102',
                    'national_id' => null,
                    'name' => 'Siti Rahmawati',
                    'date_of_birth' => '1989-07-24',
                    'sex' => 'Perempuan',
                    'phone' => '081234560102',
                    'address' => 'Jl. Karya Wisata No. 27, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0103',
                    'national_id' => null,
                    'name' => 'Ahmad Fauzan',
                    'date_of_birth' => '1982-11-05',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560103',
                    'address' => 'Jl. Sisingamangaraja No. 41, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0104',
                    'national_id' => null,
                    'name' => 'Dewi Lestari',
                    'date_of_birth' => '1995-01-18',
                    'sex' => 'Perempuan',
                    'phone' => '081234560104',
                    'address' => 'Jl. Denai No. 33, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0105',
                    'national_id' => null,
                    'name' => 'Rafi Pranata',
                    'date_of_birth' => '2014-09-21',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560105',
                    'address' => 'Jl. Turi No. 12, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0106',
                    'national_id' => null,
                    'name' => 'Nurul Aisyah',
                    'date_of_birth' => '1992-05-09',
                    'sex' => 'Perempuan',
                    'phone' => '081234560106',
                    'address' => 'Jl. Garu III No. 19, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0107',
                    'national_id' => null,
                    'name' => 'Hendra Wijaya',
                    'date_of_birth' => '1978-12-14',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560107',
                    'address' => 'Jl. Halat No. 52, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0108',
                    'national_id' => null,
                    'name' => 'Putri Maharani',
                    'date_of_birth' => '2001-08-17',
                    'sex' => 'Perempuan',
                    'phone' => '081234560108',
                    'address' => 'Jl. Pancing No. 24, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0109',
                    'national_id' => null,
                    'name' => 'Dimas Saputra',
                    'date_of_birth' => '1986-04-30',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560109',
                    'address' => 'Jl. Denai No. 47, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0110',
                    'national_id' => null,
                    'name' => 'Kartika Sari',
                    'date_of_birth' => '1998-10-11',
                    'sex' => 'Perempuan',
                    'phone' => '081234560110',
                    'address' => 'Jl. Menteng Raya No. 16, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0111',
                    'national_id' => null,
                    'name' => 'Ilham Ramadhan',
                    'date_of_birth' => '1972-06-03',
                    'sex' => 'Laki-laki',
                    'phone' => '081234560111',
                    'address' => 'Jl. Panglima Denai No. 38, Medan',
                ],
                [
                    'medical_record_number' => 'RM-2026-0112',
                    'national_id' => null,
                    'name' => 'Maya Salsabila',
                    'date_of_birth' => '2004-02-26',
                    'sex' => 'Perempuan',
                    'phone' => '081234560112',
                    'address' => 'Jl. Bajak II No. 29, Medan',
                ],
            ];

            $patientIds = [];

            foreach ($patients as $patient) {
                $existing = DB::table('patients')
                    ->where('medical_record_number', $patient['medical_record_number'])
                    ->first();

                if ($existing) {
                    $patientIds[$patient['medical_record_number']] = $existing->id;
                    continue;
                }

                $patientIds[$patient['medical_record_number']] = DB::table('patients')->insertGetId([
                    ...$patient,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
             * ============================================================
             * 4. OBAT
             * ============================================================
             */

            $medications = [
                ['name' => 'Paracetamol', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1500],
                ['name' => 'Amoxicillin', 'strength' => '500 mg', 'dosage_form' => 'Kapsul', 'unit_price' => 2500],
                ['name' => 'Cetirizine', 'strength' => '10 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1800],
                ['name' => 'Omeprazole', 'strength' => '20 mg', 'dosage_form' => 'Kapsul', 'unit_price' => 2200],
                ['name' => 'Amlodipine', 'strength' => '5 mg', 'dosage_form' => 'Tablet', 'unit_price' => 2000],
                ['name' => 'Metformin', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1700],
                ['name' => 'Ambroxol', 'strength' => '30 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1600],
                ['name' => 'Ibuprofen', 'strength' => '400 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1800],
                ['name' => 'Salbutamol', 'strength' => '2 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1200],
                ['name' => 'Loratadine', 'strength' => '10 mg', 'dosage_form' => 'Tablet', 'unit_price' => 1900],
            ];

            $medicationIds = [];

            foreach ($medications as $medication) {
                $existing = DB::table('medications')
                    ->where('name', $medication['name'])
                    ->where('strength', $medication['strength'])
                    ->where('dosage_form', $medication['dosage_form'])
                    ->first();

                if ($existing) {
                    $medicationIds[$medication['name']] = $existing->id;
                    continue;
                }

                $medicationIds[$medication['name']] = DB::table('medications')->insertGetId([
                    ...$medication,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
             * ============================================================
             * 5. KUNJUNGAN
             * ============================================================
             */

            $visits = [
                [
                    'visit_number' => 'RJ-20260930-001',
                    'patient' => 'RM-2026-0101',
                    'clinic' => 'POL-UM',
                    'visited_at' => '2026-09-30 08:05:00',
                    'queue_date' => '2026-09-30',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Batuk dan pilek sejak tiga hari terakhir.',
                    'status' => 'completed',
                    'diagnosis_code' => 'J06.9',
                    'diagnosis' => 'Infeksi saluran pernapasan akut',
                    'notes' => 'Keluhan batuk berdahak ringan disertai pilek. Tidak ditemukan sesak napas.',
                    'plan' => 'Terapi simptomatik dan anjuran istirahat yang cukup.',
                    'systolic' => 118,
                    'diastolic' => 76,
                    'temperature' => 36.8,
                    'pulse' => 82,
                    'weight' => 67.5,
                    'height' => 170,
                    'medication' => 'Ambroxol',
                    'dosage' => '3 x 1 tablet',
                    'instructions' => 'Diminum setelah makan.',
                    'quantity' => 10,
                    'invoice_status' => 'paid',
                    'payment' => 'cash',
                    'amount_received' => 100000,
                    'payment_reference' => 'CASH-260930001',
                ],
                [
                    'visit_number' => 'RJ-20260930-002',
                    'patient' => 'RM-2026-0102',
                    'clinic' => 'POL-PD',
                    'visited_at' => '2026-09-30 08:20:00',
                    'queue_date' => '2026-09-30',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Kontrol tekanan darah dan pusing ringan.',
                    'status' => 'completed',
                    'diagnosis_code' => 'I10',
                    'diagnosis' => 'Hipertensi esensial',
                    'notes' => 'Tekanan darah terkontrol. Pasien melaporkan sesekali pusing pada pagi hari.',
                    'plan' => 'Lanjutkan terapi dan kontrol tekanan darah secara berkala.',
                    'systolic' => 138,
                    'diastolic' => 86,
                    'temperature' => 36.6,
                    'pulse' => 78,
                    'weight' => 61.2,
                    'height' => 158,
                    'medication' => 'Amlodipine',
                    'dosage' => '1 x 1 tablet',
                    'instructions' => 'Diminum setiap pagi setelah sarapan.',
                    'quantity' => 14,
                    'invoice_status' => 'paid',
                    'payment' => 'qris',
                    'amount_received' => 200000,
                    'payment_reference' => 'QR-260930002',
                ],
                [
                    'visit_number' => 'RJ-20260930-003',
                    'patient' => 'RM-2026-0103',
                    'clinic' => 'POL-PD',
                    'visited_at' => '2026-09-30 08:40:00',
                    'queue_date' => '2026-09-30',
                    'queue_number' => 2,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Nyeri ulu hati terutama setelah makan.',
                    'status' => 'awaiting_payment',
                    'diagnosis_code' => 'K30',
                    'diagnosis' => 'Dispepsia',
                    'notes' => 'Keluhan rasa tidak nyaman pada ulu hati tanpa muntah.',
                    'plan' => 'Atur pola makan dan hindari makanan yang memicu keluhan.',
                    'systolic' => 122,
                    'diastolic' => 80,
                    'temperature' => 36.7,
                    'pulse' => 80,
                    'weight' => 70.1,
                    'height' => 172,
                    'medication' => 'Omeprazole',
                    'dosage' => '1 x 1 kapsul',
                    'instructions' => 'Diminum 30 menit sebelum makan pagi.',
                    'quantity' => 14,
                    'invoice_status' => 'unpaid',
                    'payment' => null,
                    'amount_received' => null,
                    'payment_reference' => null,
                ],
                [
                    'visit_number' => 'RJ-20260930-004',
                    'patient' => 'RM-2026-0104',
                    'clinic' => 'POL-UM',
                    'visited_at' => '2026-09-30 09:00:00',
                    'queue_date' => '2026-09-30',
                    'queue_number' => 2,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Bersin berulang dan hidung terasa gatal.',
                    'status' => 'in_consultation',
                    'diagnosis_code' => 'J30.9',
                    'diagnosis' => 'Rinitis alergi',
                    'notes' => 'Keluhan muncul terutama pada pagi hari dan saat terpapar debu.',
                    'plan' => 'Hindari pemicu alergi dan lakukan pengobatan sesuai resep.',
                    'systolic' => 116,
                    'diastolic' => 74,
                    'temperature' => 36.5,
                    'pulse' => 80,
                    'weight' => 55.8,
                    'height' => 160,
                    'medication' => 'Cetirizine',
                    'dosage' => '1 x 1 tablet',
                    'instructions' => 'Diminum pada malam hari.',
                    'quantity' => 10,
                    'invoice_status' => 'unpaid',
                    'payment' => null,
                    'amount_received' => null,
                    'payment_reference' => null,
                ],
                [
                    'visit_number' => 'RJ-20260930-005',
                    'patient' => 'RM-2026-0105',
                    'clinic' => 'POL-ANA',
                    'visited_at' => '2026-09-30 09:20:00',
                    'queue_date' => '2026-09-30',
                    'queue_number' => 1,
                    'queue_priority' => 'urgent',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Demam sejak tadi malam disertai lemas.',
                    'status' => 'in_consultation',
                    'diagnosis_code' => 'R50.9',
                    'diagnosis' => 'Demam',
                    'notes' => 'Anak tampak sadar dan dapat berkomunikasi dengan baik.',
                    'plan' => 'Pemantauan suhu tubuh dan pemberian terapi simptomatik.',
                    'systolic' => 110,
                    'diastolic' => 70,
                    'temperature' => 38.2,
                    'pulse' => 96,
                    'weight' => 42.3,
                    'height' => 148,
                    'medication' => 'Paracetamol',
                    'dosage' => '3 x 1 tablet',
                    'instructions' => 'Diminum setelah makan dan saat demam.',
                    'quantity' => 10,
                    'invoice_status' => 'unpaid',
                    'payment' => null,
                    'amount_received' => null,
                    'payment_reference' => null,
                ],
                [
                    'visit_number' => 'RJ-20260929-001',
                    'patient' => 'RM-2026-0106',
                    'clinic' => 'POL-UM',
                    'visited_at' => '2026-09-29 08:10:00',
                    'queue_date' => '2026-09-29',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Sakit kepala sejak dua hari.',
                    'status' => 'completed',
                    'diagnosis_code' => 'R51.9',
                    'diagnosis' => 'Sakit kepala',
                    'notes' => 'Tidak terdapat gangguan penglihatan maupun kelemahan anggota gerak.',
                    'plan' => 'Istirahat cukup dan pemantauan keluhan.',
                    'systolic' => 120,
                    'diastolic' => 78,
                    'temperature' => 36.6,
                    'pulse' => 76,
                    'weight' => 58.4,
                    'height' => 163,
                    'medication' => 'Paracetamol',
                    'dosage' => '3 x 1 tablet',
                    'instructions' => 'Diminum setelah makan bila sakit kepala.',
                    'quantity' => 10,
                    'invoice_status' => 'paid',
                    'payment' => 'cash',
                    'amount_received' => 100000,
                    'payment_reference' => 'CASH-260929001',
                ],
                [
                    'visit_number' => 'RJ-20260929-002',
                    'patient' => 'RM-2026-0107',
                    'clinic' => 'POL-PD',
                    'visited_at' => '2026-09-29 08:30:00',
                    'queue_date' => '2026-09-29',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Kontrol gula darah dan obat rutin.',
                    'status' => 'completed',
                    'diagnosis_code' => 'E11.9',
                    'diagnosis' => 'Diabetes melitus tipe 2',
                    'notes' => 'Pasien datang untuk kontrol rutin dan evaluasi terapi.',
                    'plan' => 'Lanjutkan terapi dan kontrol gula darah secara berkala.',
                    'systolic' => 132,
                    'diastolic' => 82,
                    'temperature' => 36.7,
                    'pulse' => 80,
                    'weight' => 74.6,
                    'height' => 168,
                    'medication' => 'Metformin',
                    'dosage' => '2 x 1 tablet',
                    'instructions' => 'Diminum setelah makan pagi dan malam.',
                    'quantity' => 20,
                    'invoice_status' => 'paid',
                    'payment' => 'transfer',
                    'amount_received' => 200000,
                    'payment_reference' => 'TRF-260929002',
                ],
                [
                    'visit_number' => 'RJ-20260929-003',
                    'patient' => 'RM-2026-0108',
                    'clinic' => 'POL-GIG',
                    'visited_at' => '2026-09-29 09:00:00',
                    'queue_date' => '2026-09-29',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Nyeri pada gigi geraham kanan bawah.',
                    'status' => 'completed',
                    'diagnosis_code' => 'K02.9',
                    'diagnosis' => 'Karies gigi',
                    'notes' => 'Ditemukan karies pada gigi geraham kanan bawah.',
                    'plan' => 'Dianjurkan perawatan gigi lanjutan sesuai kondisi klinis.',
                    'systolic' => 118,
                    'diastolic' => 75,
                    'temperature' => 36.5,
                    'pulse' => 78,
                    'weight' => 52.7,
                    'height' => 159,
                    'medication' => 'Ibuprofen',
                    'dosage' => '3 x 1 tablet',
                    'instructions' => 'Diminum setelah makan bila nyeri.',
                    'quantity' => 6,
                    'invoice_status' => 'paid',
                    'payment' => 'qris',
                    'amount_received' => 150000,
                    'payment_reference' => 'QR-260929003',
                ],
                [
                    'visit_number' => 'RJ-20260929-004',
                    'patient' => 'RM-2026-0109',
                    'clinic' => 'POL-MAT',
                    'visited_at' => '2026-09-29 09:30:00',
                    'queue_date' => '2026-09-29',
                    'queue_number' => 1,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Mata merah dan terasa gatal.',
                    'status' => 'awaiting_pharmacy',
                    'diagnosis_code' => 'H10.9',
                    'diagnosis' => 'Konjungtivitis',
                    'notes' => 'Mata kanan tampak kemerahan tanpa gangguan penglihatan berat.',
                    'plan' => 'Jaga kebersihan mata dan hindari menggosok mata.',
                    'systolic' => 124,
                    'diastolic' => 78,
                    'temperature' => 36.6,
                    'pulse' => 82,
                    'weight' => 68.3,
                    'height' => 171,
                    'medication' => 'Loratadine',
                    'dosage' => '1 x 1 tablet',
                    'instructions' => 'Diminum pada malam hari.',
                    'quantity' => 5,
                    'invoice_status' => 'unpaid',
                    'payment' => null,
                    'amount_received' => null,
                    'payment_reference' => null,
                ],
                [
                    'visit_number' => 'RJ-20260929-005',
                    'patient' => 'RM-2026-0110',
                    'clinic' => 'POL-UM',
                    'visited_at' => '2026-09-29 10:00:00',
                    'queue_date' => '2026-09-29',
                    'queue_number' => 2,
                    'queue_priority' => 'normal',
                    'visit_type' => 'outpatient',
                    'payment_method' => 'general',
                    'complaint' => 'Nyeri pada lutut setelah aktivitas.',
                    'status' => 'completed',
                    'diagnosis_code' => 'M25.5',
                    'diagnosis' => 'Nyeri sendi',
                    'notes' => 'Nyeri terutama dirasakan setelah aktivitas fisik.',
                    'plan' => 'Kurangi aktivitas yang memicu nyeri dan lakukan pemantauan.',
                    'systolic' => 126,
                    'diastolic' => 80,
                    'temperature' => 36.6,
                    'pulse' => 79,
                    'weight' => 72.5,
                    'height' => 169,
                    'medication' => 'Ibuprofen',
                    'dosage' => '2 x 1 tablet',
                    'instructions' => 'Diminum setelah makan bila nyeri.',
                    'quantity' => 10,
                    'invoice_status' => 'paid',
                    'payment' => 'cash',
                    'amount_received' => 100000,
                    'payment_reference' => 'CASH-260929005',
                ],
            ];

            /*
             * ============================================================
             * 6. PROSES KUNJUNGAN
             * ============================================================
             */

            foreach ($visits as $data) {

                $existingVisit = DB::table('visits')
                    ->where('visit_number', $data['visit_number'])
                    ->first();

                if ($existingVisit) {
                    continue;
                }

                $visitId = DB::table('visits')->insertGetId([
                    'visit_number' => $data['visit_number'],
                    'patient_id' => $patientIds[$data['patient']],
                    'doctor_id' => $doctorIds[$data['clinic']],
                    'clinic_id' => $clinicIds[$data['clinic']],
                    'registered_by' => $administrationUser->id,
                    'visited_at' => $data['visited_at'],
                    'queue_date' => $data['queue_date'],
                    'queue_number' => $data['queue_number'],
                    'queue_priority' => $data['queue_priority'],
                    'visit_type' => $data['visit_type'],
                    'payment_method' => $data['payment_method'],
                    'complaint' => $data['complaint'],
                    'status' => $data['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /*
                 * Pemeriksaan
                 */

                DB::table('examinations')->insert([
                    'visit_id' => $visitId,
                    'doctor_id' => $doctorIds[$data['clinic']],
                    'diagnosis_code' => $data['diagnosis_code'],
                    'diagnosis' => $data['diagnosis'],
                    'notes' => $data['notes'],
                    'plan' => $data['plan'],
                    'systolic_pressure' => $data['systolic'],
                    'diastolic_pressure' => $data['diastolic'],
                    'temperature_c' => $data['temperature'],
                    'pulse_rate' => $data['pulse'],
                    'weight_kg' => $data['weight'],
                    'height_cm' => $data['height'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /*
                 * Resep
                 */

                $medicationId = $medicationIds[$data['medication']];

                $medication = DB::table('medications')
                    ->where('id', $medicationId)
                    ->first();

                $totalPrice = $medication->unit_price * $data['quantity'];

                $prescriptionStatus =
                    in_array($data['status'], ['completed', 'awaiting_pharmacy'], true)
                        ? 'dispensed'
                        : 'pending';

                DB::table('prescriptions')->insert([
                    'visit_id' => $visitId,
                    'prescribed_by' => $doctorUser->id,
                    'medication_id' => $medicationId,
                    'medication_name' => $medication->name,
                    'dosage' => $data['dosage'],
                    'instructions' => $data['instructions'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $medication->unit_price,
                    'total_price' => $totalPrice,
                    'status' => $prescriptionStatus,
                    'dispensed_by' =>
                        $prescriptionStatus === 'dispensed'
                            ? $pharmacyUser->id
                            : null,
                    'dispensed_at' =>
                        $prescriptionStatus === 'dispensed'
                            ? now()
                            : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /*
                 * Invoice
                 */

                $clinic = DB::table('clinics')
                    ->where('id', $clinicIds[$data['clinic']])
                    ->first();

                $invoiceAmount = $clinic->consultation_fee + $totalPrice;

                $amountReceived = $data['amount_received'];

                $changeAmount =
                    $amountReceived !== null
                        ? max(0, $amountReceived - $invoiceAmount)
                        : null;

                $paid = $data['invoice_status'] === 'paid';

                DB::table('invoices')->insert([
                    'visit_id' => $visitId,
                    'amount' => $invoiceAmount,
                    'status' => $data['invoice_status'],
                    'paid_by' => $paid ? $cashierUser->id : null,
                    'paid_at' => $paid ? now() : null,
                    'payment_method' => $data['payment'],
                    'amount_received' => $amountReceived,
                    'change_amount' => $changeAmount,
                    'payment_reference' => $data['payment_reference'],
                    'payment_notes' => $paid
                        ? 'Pembayaran telah diterima.'
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
             * ============================================================
             * 7. COUNTER ANTREAN
             * ============================================================
             */

            foreach ($visits as $data) {
                DB::table('daily_queue_counters')->updateOrInsert(
                    [
                        'clinic_id' => $clinicIds[$data['clinic']],
                        'queue_date' => $data['queue_date'],
                    ],
                    [
                        'last_number' => DB::table('visits')
                            ->where('clinic_id', $clinicIds[$data['clinic']])
                            ->where('queue_date', $data['queue_date'])
                            ->max('queue_number') ?? 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        });

        $this->command?->info('Dataset operasional berhasil ditambahkan.');
    }
}