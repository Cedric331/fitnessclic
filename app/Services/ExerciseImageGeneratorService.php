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
            'model' => 'gpt-image-1-mini',
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'auto',
            'n' => 1,
            'background' => 'transparent',
            'output_format' => 'webp',
            'output_compression' => 60,

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
        $genderText = $gender === 'femme' ? 'Femme' : 'Homme';
        $descriptionText = $description ? $description : '';
    
        return sprintf(
            "Illustration vectorielle semi-flat, propre et nette, style application de fitness moderne.
            Personnage unique, proportions réalistes, anatomie correcte.
            Vue de profil (côté), plan large, corps entier visible.
            Contours fins mais bien définis, aplats de couleur avec ombrage doux (cell shading léger).
            Style pédagogique, clair et lisible, pas de texture, pas de grain.

            Tenue standardisée :
            - T-shirt de sport bleu foncé
            - Pantalon de sport noir ajusté
            - Baskets noires avec semelle blanche

            Personnage placé sur un tapis de yoga bleu clair.
            Ombre douce sous le tapis et le corps.
            Fond blanc uni, sans décor.

            Aucun texte, aucun logo, aucun watermark.
            Composition centrée avec marges blanches régulières


            Sexe du personnage : %s

            Caractéristiques physiques selon le sexe :
            - Homme : silhouette masculine athlétique standard, épaules légèrement plus larges.
            - Femme : silhouette féminine athlétique standard, taille légèrement marquée, hanches naturelles.
            Toujours une musculature réaliste et non exagérée.

            Exercice : %s

            Description précise de la posture :
            %s

            Contraintes :
            - Montrer clairement la posture finale
            - Articulations plausibles
            - Position stable et équilibrée
            - Alignement correct du dos, des jambes et des bras


            ---------------------------------------

            NÉGATIF (fortement recommandé)
            photoréaliste, rendu 3D, style cinéma, style manga, cartoon exagéré,
            muscles hypertrophiés, proportions irréalistes,
            croquis, peinture, aquarelle, bruit, grain,
            texte, logo, watermark,
            arrière-plan détaillé, décor de salle de sport,
            plusieurs personnages, gros plan, membres coupés

            ---------------------------------------",
            $genderText,
            $exercise,
            $descriptionText
        );        
    }
    
}
