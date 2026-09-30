<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'administrasi') ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $patient = $this->route('patient');

        abort_unless($patient instanceof Patient, 404);

        return [
            'national_id' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('patients', 'national_id')->ignore($patient),
            ],
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['required', Rule::in(['Perempuan', 'Laki-laki'])],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'national_id.unique' => 'NIK sudah digunakan oleh pasien lain.',
        ];
    }
}
