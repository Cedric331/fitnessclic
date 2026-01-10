<?php

namespace App\Services;

use OpenAI\Client;
use InvalidArgumentException;

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
     * @param string $gender The gender of the person (homme/femme)
     * @param string|null $description Optional description to add to the prompt
     * @return array{base64: string, prompt: string} The base64 encoded image and the prompt used
     * @throws InvalidArgumentException|\Exception
     */
    public function generate(string $exercise, string $gender, ?string $description = null): array
    {
        $genderLabel = $this->normalizeGender($gender);

        $prompt = $this->buildPrompt(
            exercise: $exercise,
            gender: $genderLabel,
            description: $description
        );

        $response = $this->client->images()->create([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1,
            'response_format' => 'b64_json',
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
    protected function buildPrompt(string $exercise, string $gender, ?string $description = null): string
    {
        $personLabel = $gender === 'femme' ? 'a woman' : 'a man';
        $descriptionText = $description ? " Exercise details: {$description}." : '';

        return sprintf(
//             "Clean, minimalist flat illustration of %s doing the fitness exercise: %s.%s 
// The person wears simple sportswear (t-shirt and shorts). 
// Modern vector art style with soft neutral colors (beige, cream, light gray). 
// Clean background, gym or home interior setting. 
// Side view, correct exercise form and posture. 
// Professional fitness illustration style, no text, no logos, no photorealism.",
"Minimalist flat illustration of a %s performing the following exercise: %s. %s
Clean vector style, soft neutral color palette (beige, off-white, light gray),
smooth shapes, subtle shadows, clean background without unnecessary elements, bright and minimalist interior,
simple walls and floor, modern wellness aesthetic.
The person is fully dressed in understated sportswear.
Clear posture, correct execution of the exercise, profile view.
Very consistent style, calm and professional fitness illustration, without text, logo, realism, or photorealistic rendering.",
            $personLabel,
            $exercise,
            $descriptionText
        );
    }

    /**
     * Normalize the gender input
     */
    protected function normalizeGender(string $gender): string
    {
        return match (strtolower($gender)) {
            'femme', 'female', 'f' => 'femme',
            'homme', 'male', 'm' => 'homme',
            default => throw new InvalidArgumentException('Sexe invalide. Utilisez "homme" ou "femme".'),
        };
    }
}
