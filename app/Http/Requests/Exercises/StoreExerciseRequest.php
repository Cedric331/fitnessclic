<?php

namespace App\Http\Requests\Exercises;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreExerciseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'suggested_duration' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'exists:categories,id'],
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $categoryIds = $this->input('category_ids', []);

            if (!empty($categoryIds)) {
                $categories = Category::whereIn('id', $categoryIds)
                    ->where(function ($query) {
                        $query->where('type', 'public')
                            ->orWhere(function ($q) {
                                $q->where('type', 'private')
                                  ->where('user_id', Auth::id());
                            });
                    })
                    ->get();

                if ($categories->count() !== count($categoryIds)) {
                    $validator->errors()->add(
                        'category_ids',
                        'Une ou plusieurs catégories ne sont pas valides ou ne vous appartiennent pas.'
                    );
                }
            }
        });
    }
}

