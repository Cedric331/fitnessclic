<?php

namespace Database\Factories;

use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionLayout>
 */
class SessionLayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'layout_data' => ['test' => 'data'],
            'canvas_width' => 800,
            'canvas_height' => 1200,
            'pdf_path' => null,
        ];
    }
}

