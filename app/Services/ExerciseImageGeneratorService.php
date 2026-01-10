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
            // 'model' => 'gpt-image-1',
            // 'prompt' => $prompt,
            // 'size' => '1024x1024',
            // 'quality' => 'auto',
            // 'n' => 1,

            'model' => 'dall-e-3',
            'prompt' => $prompt, 
            'size' => '1024x1024', 
            'quality' => 'hd', 
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
    protected function buildPrompt(string $exercise, ?string $description = null): string
    {
        $descriptionText = $description ? " Description de l'exercice: {$description}." : '';

        return sprintf(
            "Illustration plate minimaliste et pédagogique d’un mannequin  générique réalisant l’exercice suivant : %s.%s

            Mannequin humain neutre, stylisé, sans traits distinctifs, sans identité culturelle, sans expression faciale marquée.
            Aucun accessoire : pas de chapeau, pas de couvre-chef, pas de bijoux, pas de montre, pas de bandeau, pas de cheveux visibles.
            Tenue de sport simple et moderne (débardeur ou t-shirt uni + short), couleurs neutres.

            Style graphique strictement vectoriel et plat (flat design),
            formes simples et géométriques, contours nets,
            ombres très légères ou absentes,
            aucun effet peinture, aucun effet bande dessinée, aucun trait illustratif.

            Palette de couleurs douces et neutres (beige, blanc cassé, gris clair),
            fond uni clair ou très légèrement texturé,
            aucun décor narratif, aucun objet, aucun élément superflu.

            STYLE (obligatoire):
                - Flat vector / formes simples / aplats de couleur / contours nets.
                - Pas de style BD, pas de croquis, pas de texture, pas de rendu peinture.
                - Ombre: une ombre douce unique au sol.

            PERSONNAGE (obligatoire):
                - Mannequin neutre et anonyme, visage très simple ou sans visage (faceless).
                - Tête nue: aucun chapeau, aucun couvre-chef, aucun bandeau, aucun foulard, aucun casque.
                - Pas de cheveux détaillés, pas de barbe.
                - Tenue simple: t-shirt ou débardeur uni + short uni + baskets simples. Aucune marque.

            Posture anatomiquement correcte, exécution précise de l’exercice,
            vue de profil ou trois-quarts selon la lisibilité du mouvement.

            Illustration professionnelle destinée à une application de création de séances de sport.
            Aucun texte, aucun logo, aucun réalisme, aucun rendu photoréaliste.
            ",
            $exercise,
            $descriptionText
        );
    }
}
