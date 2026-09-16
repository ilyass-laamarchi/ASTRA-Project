<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/** Accepts only identity/date/message fields; all commercial values come from Laravel. */
class StoreReservationRequest extends FormRequest
{
    /** Allows reservation creation only for an authenticated client account. */
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_CLIENT;
    }

    /** Accepts only car, dates, and an optional message; price fields are excluded. */
    public function rules(): array
    {
        return [
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'client_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Returns clear French date validation messages to the booking form. */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Veuillez sélectionner une date de début.',
            'end_date.required' => 'Veuillez sélectionner une date de fin.',
            'start_date.date_format' => 'Veuillez utiliser une date de début valide.',
            'end_date.date_format' => 'Veuillez utiliser une date de fin valide.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
