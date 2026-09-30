<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExaminationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'dokter') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'diagnosis_code' => ['nullable', 'string', 'max:16'],
            'diagnosis' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:10000'],
            'systolic_pressure' => ['nullable', 'integer', 'min:0', 'max:999'],
            'diastolic_pressure' => ['nullable', 'integer', 'min:0', 'max:999'],
            'temperature_c' => ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:99.9'],
            'pulse_rate' => ['nullable', 'integer', 'min:0', 'max:999'],
            'weight_kg' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999.99'],
            'height_cm' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999.99'],
            'plan' => ['nullable', 'string', 'max:5000'],
            'medication_id' => [
                'nullable',
                'integer',
                Rule::exists('medications', 'id')->where('status', 'active'),
            ],
            'dosage' => [
                Rule::requiredIf($this->filled('medication_id')),
                'nullable',
                'string',
                'max:255',
            ],
            'quantity' => [
                Rule::requiredIf($this->filled('medication_id')),
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
