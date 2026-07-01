<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Géocodage de communes françaises via l'API Géo (geo.api.gouv.fr).
 *
 * Service public et gratuit du gouvernement français, sans clé d'API.
 * @see https://geo.api.gouv.fr/decoupage-administratif/communes
 */
class GeocodingService
{
    private const BASE_URL = 'https://geo.api.gouv.fr/communes';

    /**
     * Résout une ville (et éventuellement son code postal) en coordonnées.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeCity(?string $city, ?string $postalCode = null): ?array
    {
        $city = trim((string) $city);

        if ($city === '') {
            return null;
        }

        $postalCode = trim((string) $postalCode);
        $cacheKey = 'geocode:'.Str::slug($city).':'.$postalCode;

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($city, $postalCode) {
            $params = [
                'nom' => $city,
                'fields' => 'centre,codesPostaux',
                'boost' => 'population',
                'limit' => 5,
            ];

            if ($postalCode !== '' && preg_match('/^\d{5}$/', $postalCode)) {
                $params['codePostal'] = $postalCode;
                unset($params['nom']);
            }

            try {
                $response = Http::timeout(5)->retry(1, 200)->get(self::BASE_URL, $params);
            } catch (\Throwable $e) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $communes = $response->json();

            if (! is_array($communes) || $communes === []) {
                return null;
            }

            // Si on a filtré par nom et code postal, privilégier la commune
            // dont le code postal correspond exactement.
            if ($postalCode !== '') {
                foreach ($communes as $commune) {
                    if (in_array($postalCode, $commune['codesPostaux'] ?? [], true)) {
                        return $this->extractCoordinates($commune);
                    }
                }
            }

            return $this->extractCoordinates($communes[0]);
        });
    }

    /**
     * @param  array<string, mixed>  $commune
     * @return array{lat: float, lng: float}|null
     */
    private function extractCoordinates(array $commune): ?array
    {
        // centre = GeoJSON Point : coordinates = [longitude, latitude]
        $coordinates = $commune['centre']['coordinates'] ?? null;

        if (! is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        return [
            'lat' => (float) $coordinates[1],
            'lng' => (float) $coordinates[0],
        ];
    }
}
