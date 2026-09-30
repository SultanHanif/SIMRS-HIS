<?php

namespace App\Http\Requests;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'administrasi') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('status', 'active'),
            ],
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('clinics', 'id')->where('status', 'active'),
            ],
            'visited_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['general'])],
            'complaint' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['doctor_id', 'clinic_id'])) {
                    return;
                }

                $doctor = Doctor::query()->with('user')->find($this->integer('doctor_id'));

                if ($doctor && $doctor->clinic_id !== $this->integer('clinic_id')) {
                    $validator->errors()->add('doctor_id', 'Dokter tidak bertugas di poli yang dipilih.');
                }

                if ($doctor && $doctor->status !== 'active') {
                    $validator->errors()->add('doctor_id', 'Dokter yang dipilih tidak aktif.');
                }

                if (! $doctor?->user?->is_active || ! $doctor->user->hasRole('dokter')) {
                    $validator->errors()->add('doctor_id', 'Akun dokter harus aktif dan terhubung ke profil dokter.');
                }

                $clinic = Clinic::query()->find($this->integer('clinic_id'));

                if ($clinic && $clinic->consultation_fee < 1) {
                    $validator->errors()->add('clinic_id', 'Tarif konsultasi poli belum diatur oleh administrator.');
                }
            },
        ];
    }
}
