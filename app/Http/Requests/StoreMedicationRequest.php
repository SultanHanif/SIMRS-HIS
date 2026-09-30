<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'farmasi') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('medications')->where('strength', $this->input('strength'))->where('dosage_form', $this->input('dosage_form'))],
            'strength' => ['required', 'string', 'max:50'],
            'dosage_form' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
