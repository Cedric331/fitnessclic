<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCoach() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            // Tarif horaire saisi en euros (entier ou décimal), converti en centimes.
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'city' => ['nullable', 'string', 'max:255', 'required_if:is_published,true'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'], // 5 Mo
        ];
    }

    public function messages(): array
    {
        return [
            'city.required_if' => 'La ville est requise pour publier votre profil.',
        ];
    }
}
