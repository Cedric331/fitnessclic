<?php

namespace App\Http\Requests\Coach;

use App\Enums\CoachingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Coordonnées issues de l'autocomplétion de ville (API Géo gouv.fr).
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'coaching_mode' => ['nullable', Rule::enum(CoachingMode::class)],
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
