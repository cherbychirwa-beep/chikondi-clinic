<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for both registering and updating a patient. */
class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('patient') ? 'patients.update' : 'patients.register';

        return $this->user()?->hasPermission($permission) ?? false;
    }

    public function rules(): array
    {
        $phone = ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-]{6,18}$/'];

        return [
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => $phone,
            'next_of_kin_name' => ['required', 'string', 'min:3', 'max:150'],
            'next_of_kin_phone' => $phone,
            'primary_doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid telephone number (digits, spaces or dashes; optional leading +).',
            'next_of_kin_phone.regex' => 'Enter a valid telephone number (digits, spaces or dashes; optional leading +).',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
            'primary_doctor_id.required' => 'Please select a registered doctor.',
        ];
    }
}
