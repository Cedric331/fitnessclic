<?php

namespace App\Support;

/**
 * Catalogue des disciplines de coaching utilisé pour les pages
 * d'atterrissage SEO (/coachs/discipline/{slug}) et le sitemap.
 *
 * Chaque discipline expose :
 *  - slug  : segment d'URL stable et indexable
 *  - name  : libellé affiché (H1, titres)
 *  - terms : termes recherchés dans les champs coach (specialties, headline, bio)
 */
class CoachTaxonomy
{
    /**
     * @return array<int, array{slug: string, name: string, terms: array<int, string>}>
     */
    public static function disciplines(): array
    {
        return [
            ['slug' => 'musculation', 'name' => 'Musculation', 'terms' => ['musculation', 'muscu', 'renforcement']],
            ['slug' => 'perte-de-poids', 'name' => 'Perte de poids', 'terms' => ['perte de poids', 'minceur', 'perte de gras']],
            ['slug' => 'yoga-pilates', 'name' => 'Yoga & Pilates', 'terms' => ['yoga', 'pilates']],
            ['slug' => 'cardio-hiit', 'name' => 'Cardio & HIIT', 'terms' => ['cardio', 'hiit']],
            ['slug' => 'course-a-pied', 'name' => 'Course à pied', 'terms' => ['course à pied', 'running']],
            ['slug' => 'boxe', 'name' => 'Boxe', 'terms' => ['boxe']],
            ['slug' => 'preparation-physique', 'name' => 'Préparation physique', 'terms' => ['préparation physique', 'prépa physique']],
            ['slug' => 'remise-en-forme', 'name' => 'Remise en forme', 'terms' => ['remise en forme', 'fitness']],
        ];
    }

    /**
     * Retrouve une discipline par son slug d'URL.
     *
     * @return array{slug: string, name: string, terms: array<int, string>}|null
     */
    public static function findDiscipline(string $slug): ?array
    {
        foreach (self::disciplines() as $discipline) {
            if ($discipline['slug'] === $slug) {
                return $discipline;
            }
        }

        return null;
    }
}
