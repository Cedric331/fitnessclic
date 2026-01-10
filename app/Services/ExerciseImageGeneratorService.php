<?php

namespace App\Services;

use OpenAI\Client;

class ExerciseImageGeneratorService
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Generate an exercise image using OpenAI's image generation API
     * 
     * @param string $exercise The exercise name
     * @param string|null $description Optional description to add to the prompt
     * @return array{base64: string, prompt: string} The base64 encoded image and the prompt used
     * @throws \Exception
     */
    public function generate(string $exercise, ?string $description = null): array
    {
        $prompt = $this->buildPrompt(
            exercise: $exercise,
            description: $description
        );

        $response = $this->client->images()->create([
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'auto',
            'n' => 1,
        ]);

        // The response data property name varies by version - try both
        $imageData = $response->data[0];
        $base64 = $imageData->b64_json ?? $imageData->b64Json ?? null;
        
        if (!$base64) {
            throw new \Exception('Unable to get base64 image from response: ' . json_encode($imageData));
        }

        return [
            'base64' => $base64,
            'prompt' => $prompt,
        ];
    }

    /**
     * Save a base64 image to a temporary file and return the path
     * 
     * @param string $base64Image The base64 encoded image
     * @param string $filename The desired filename (without extension)
     * @return string The path to the saved temporary file
     */
    public function saveToTempFile(string $base64Image, string $filename): string
    {
        $imageData = base64_decode($base64Image);
        $tempPath = sys_get_temp_dir() . '/' . $filename . '_' . uniqid() . '.png';
        
        file_put_contents($tempPath, $imageData);
        
        return $tempPath;
    }

    /**
     * Build the prompt for image generation
     */
    protected function buildPrompt(string $exercise, ?string $description = null): string
    {
        $descriptionText = $description ? " Exercise details: {$description}." : '';

        return sprintf(
            "Minimalist flat illustration of a fitness model performing the following exercise: %s.%s
Clean vector style, soft neutral color palette (beige, off-white, light gray),
smooth shapes, subtle shadows, clean background without unnecessary elements, bright and minimalist interior,
simple walls and floor, modern wellness aesthetic.
The model is fully dressed in understated sportswear without a hat, a distinctive sign and without any other clothing.
Clear posture, correct execution of the exercise, profile view.
Very consistent style, calm and professional fitness illustration, without text, logo, realism, or photorealistic rendering.",
            $exercise,
            $descriptionText
        );
    }
}
