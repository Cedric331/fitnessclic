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
     * @param string $gender The gender of the person ('homme' or 'femme')
     * @return array{base64: string, prompt: string} The base64 encoded image and the prompt used
     * @throws \Exception
     */
    public function generate(string $exercise, ?string $description = null, string $gender = 'homme'): array
    {
        $prompt = $this->buildPrompt(
            exercise: $exercise,
            description: $description,
            gender: $gender
        );

        $response = $this->client->images()->create([
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'auto',
            'n' => 1,

            // 'model' => 'dall-e-3',
            // 'prompt' => $prompt, 
            // 'size' => '1024x1024', 
            // 'quality' => 'hd', 
            // 'n' => 1, 
            // 'response_format' => 'b64_json',
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
    protected function buildPrompt(string $exercise, ?string $description = null, string $gender = 'homme'): string
    {
        $descriptionText = $description ? " Description de l'exercice: {$description}." : '';

        return sprintf(
            "Illustration plate minimaliste d'une %s effectuant l'exercice %s.%s Style vectoriel épuré, palette de couleurs neutres et douces (beige, blanc cassé, gris clair), 
            formes lisses, ombres subtiles, arrière-plan épuré sans éléments superflus, intérieur lumineux et minimaliste, murs et sol simples, 
            esthétique bien-être moderne. La personne est athlétique, porte un débardeur blanc, un short noir et des baskets blanches. 
            Posture claire, exécution correcte de l'exercice, vue de profil. Style très cohérent, illustration fitness calme et professionnelle, sans texte, sans logo, sans réalisme, sans rendu photo.",
            $gender,
            $exercise,
            $descriptionText
        );
    }
}
