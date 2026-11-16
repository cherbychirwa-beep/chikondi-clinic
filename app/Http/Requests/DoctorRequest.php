<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('doctors.manage') ?? false;
    }

    public function rules(): array
    {
        $doctor = $this->route('doctor');

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor?->user_id)],
            'doctor_number' => [$doctor ? 'required' : 'nullable', 'string', 'max:20', Rule::unique('doctors', 'doctor_number')->ignore($doctor?->id)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-]{6,18}$/'],
            'consulting_room' => ['required', 'string', 'max:20'],
            'employment_type' => ['required', Rule::in(['full_time', 'visiting'])],
            'next_visit_date' => ['required_if:employment_type,visiting', 'nullable', 'date'],
            'next_visit_duration_days' => ['required_if:employment_type,visiting', 'nullable', 'integer', 'min:1', 'max:60'],
        ];

        if (! $doctor) {
            $rules['username'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'];
            $rules['password'] = ['required', Password::min(8)->letters()->numbers()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid telephone number.',
            'next_visit_date.required_if' => 'The next visit date is required for visiting doctors.',
            'next_visit_duration_days.required_if' => 'The visit duration (days) is required for visiting doctors.',
        ];
    }
}
