<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates public registration without accepting any role field. */
class RegisterRequest extends FormRequest
{
    /** Allows unauthenticated visitors to submit the public registration form. */
    public function authorize(): bool
    {
        return true;
    }

    /** Defines identity/password fields and explicitly prohibits role escalation fields. */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['prohibited'],
            'role_id' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'is_staff' => ['prohibited'],
            'user_type' => ['prohibited'],
            'permissions' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    /** Returns clear French validation messages for the public form. */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'prohibited' => 'Ce champ n’est pas autorisé lors d’une inscription publique.',
        ];
    }
}
