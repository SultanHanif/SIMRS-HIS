<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'kasir') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $invoiceAmount = (int) ($this->route('visit')?->invoice?->amount ?? 0);

        return [
            'payment_method' => ['required', Rule::in(['cash', 'qris', 'bank_transfer', 'card'])],
            'amount_received' => [
                'required',
                'integer',
                "min:{$invoiceAmount}",
                Rule::when($this->input('payment_method') !== 'cash', Rule::in([$invoiceAmount])),
            ],
            'payment_reference' => [
                Rule::requiredIf($this->input('payment_method') !== 'cash'),
                'nullable',
                'string',
                'max:100',
            ],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
