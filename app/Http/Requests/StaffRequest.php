<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('staff.manage') ?? false;
    }

    public function rules(): array
    {
        $editing = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'role_id' => ['required', Rule::exists('roles', 'id')->whereIn('slug', ['admin', 'receptionist', 'attendant'])],
        ];

        if (! $editing) {
            $rules['username'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'];
            $rules['password'] = ['required', Password::min(8)->letters()->numbers()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['username.regex' => 'Usernames may contain letters, numbers, dots, dashes and underscores only.'];
    }
}
