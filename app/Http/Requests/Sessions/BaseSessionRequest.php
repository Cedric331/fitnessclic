<?php

namespace App\Http\Requests\Sessions;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseSessionRequest extends FormRequest
{
    /**
     * Règles de validation communes pour les exercices
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function exerciseRules(): array
    {
        return [
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'array'],
            'exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1'],
            'exercises.*.sets.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.sets.*.weight' => ['nullable', 'numeric'],
            'exercises.*.sets.*.rest_time' => ['nullable', 'string'],
            'exercises.*.sets.*.duration' => ['nullable', 'string'],
            'exercises.*.sets.*.use_duration' => ['nullable', 'boolean'],
            'exercises.*.sets.*.use_bodyweight' => ['nullable', 'boolean'],
            'exercises.*.sets.*.order' => ['required', 'integer', 'min:0'],
            'exercises.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.weight' => ['nullable', 'numeric'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.sets_count' => ['nullable', 'integer', 'min:1'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
            'exercises.*.custom_exercise_name' => ['nullable', 'string', 'max:255'],
            'exercises.*.use_duration' => ['nullable', 'boolean'],
            'exercises.*.use_bodyweight' => ['nullable', 'boolean'],
            'exercises.*.block_id' => ['nullable', 'integer'],
            'exercises.*.block_type' => ['nullable', 'in:standard,set'],
            'exercises.*.position_in_block' => ['nullable', 'integer', 'min:0', 'max:6'],
        ];
    }

    /**
     * Règles de validation communes pour les clients
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function customerRules(): array
    {
        return [
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['required', 'integer', 'exists:customers,id'],
        ];
    }
}
