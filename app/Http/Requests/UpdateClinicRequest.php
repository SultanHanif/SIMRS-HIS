<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16', Rule::unique('clinics', 'code')->ignore($this->route('clinic'))],
            'name' => ['required', 'string', 'max:255', Rule::unique('clinics', 'name')->ignore($this->route('clinic'))],
            'consultation_fee' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
