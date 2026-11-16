<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payments.record') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount_paid' => ['required', 'numeric', 'gt:0', 'max:99999999', 'regex:/^\d+(\.\d{1,2})?$/'],
            'payment_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'submission_token' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return ['amount_paid.regex' => 'Enter an amount with at most two decimal places.'];
    }
}
