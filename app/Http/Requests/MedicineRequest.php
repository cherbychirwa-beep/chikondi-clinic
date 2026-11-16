<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('medicines.manage') ?? false;
    }

    public function rules(): array
    {
        $medicine = $this->route('medicine');

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('medicines', 'name')->where('strength', $this->input('strength'))->ignore($medicine?->id),
            ],
            'generic_name' => ['nullable', 'string', 'max:150'],
            'strength' => ['nullable', 'string', 'max:50'],
            'dosage_form' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'This medicine with the same strength already exists in the catalogue.'];
    }
}
