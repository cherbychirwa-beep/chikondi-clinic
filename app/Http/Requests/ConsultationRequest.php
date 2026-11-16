<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('consultations.perform') ?? false;
    }

    public function rules(): array
    {
        return [
            'consultation_date' => ['required', 'date', 'before_or_equal:today'],
            'diagnosis' => ['required', 'string', 'min:3', 'max:2000'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'prescriptions' => ['nullable', 'array', 'max:30'],
            'prescriptions.*.medicine_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('medicines', 'id')->where('is_active', true),
            ],
            'prescriptions.*.dosage' => ['required', 'string', 'max:100'],
            'prescriptions.*.frequency' => ['required', 'string', 'max:100'],
            'prescriptions.*.duration' => ['required', 'string', 'max:100'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'prescriptions.*.medicine_id' => 'medicine',
            'prescriptions.*.dosage' => 'dosage',
            'prescriptions.*.frequency' => 'frequency',
            'prescriptions.*.duration' => 'duration',
        ];
    }

    public function messages(): array
    {
        return ['prescriptions.*.medicine_id.distinct' => 'The same medicine cannot be prescribed twice in one consultation.'];
    }
}
