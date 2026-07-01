<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Services\GeocodingService;
use App\Support\CoachTaxonomy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CoachDirectoryController extends Controller
{
    /** Rayons autorisés pour la recherche aux alentours (km). */
    private const ALLOWED_RADII = [10, 25, 50, 100];

    private const DEFAULT_RADIUS = 25;

    public function __construct(private readonly GeocodingService $geocoder)
    {
    }

    /**
     * Public directory of published coaches (superprof-style).
     */
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'city' => trim((string) $request->query('city', '')),
            'specialty' => trim((string) $request->query('specialty', '')),
            'min_rate' => trim((string) $request->query('min_rate', '')),
            'max_rate' => trim((string) $request->query('max_rate', '')),
            'around' => $request->boolean('around'),
            'radius' => $this->normalizeRadius($request->query('radius')),
            'lat' => $this->normalizeCoordinate($request->query('lat'), 90),
            'lng' => $this->normalizeCoordinate($request->query('lng'), 180),
        ];

        // Tarif saisi en euros → comparé aux centimes stockés.
        $minCents = is_numeric($filters['min_rate']) ? (int) round((float) $filters['min_rate'] * 100) : null;
        $maxCents = is_numeric($filters['max_rate']) ? (int) round((float) $filters['max_rate'] * 100) : null;

        // Recherche par proximité : autour de la position GPS de l'utilisateur
        // (coordonnées fournies, sans ville) OU autour d'une ville saisie.
        $useProximity = $filters['around'];
        if ($useProximity && ($filters['lat'] === null || $filters['lng'] === null) && $filters['city'] !== '') {
            $coordinates = $this->geocoder->geocodeCity($filters['city']);
            $filters['lat'] = $coordinates['lat'] ?? null;
            $filters['lng'] = $coordinates['lng'] ?? null;
        }
        $useProximity = $useProximity && $filters['lat'] !== null && $filters['lng'] !== null;

        $coaches = CoachProfile::query()
            ->published()
            ->with('user:id,name,avatar_url,created_at')
            ->with('media')
            ->when($useProximity, fn ($q) => $q->nearby($filters['lat'], $filters['lng'], (float) $filters['radius']))
            ->when(! $useProximity && $filters['city'] !== '', fn ($q) => $q->where('city', 'like', "%{$filters['city']}%"))
            ->when($filters['specialty'] !== '', fn ($q) => $q->where('specialties', 'like', "%{$filters['specialty']}%"))
            ->when($minCents !== null, fn ($q) => $q->whereNotNull('hourly_rate')->where('hourly_rate', '>=', $minCents))
            ->when($maxCents !== null, fn ($q) => $q->whereNotNull('hourly_rate')->where('hourly_rate', '<=', $maxCents))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $term = $filters['q'];
                $q->where(function ($sub) use ($term) {
                    $sub->where('headline', 'like', "%{$term}%")
                        ->orWhere('bio', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"));
                });
            })
            ->when(! $useProximity, fn ($q) => $q->latest('published_at'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CoachProfile $p) => $p->toCard());

        return Inertia::render('coachs/Index', [
            'coaches' => $coaches,
            'filters' => $filters,
        ]);
    }

    /**
     * Page d'atterrissage SEO « Coach sportif à {ville} ».
     */
    public function cityLanding(Request $request, string $city): Response
    {
        // Retrouver le libellé exact d'une ville de coach publié depuis son slug.
        $cityName = $this->publishedCities()
            ->first(fn (string $name) => Str::slug($name) === $city);

        abort_if($cityName === null, 404);

        $coaches = $this->baseQuery()
            ->where('city', $cityName)
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CoachProfile $p) => $p->toCard());

        return Inertia::render('coachs/Landing', [
            'coaches' => $coaches,
            'landing' => [
                'heading' => "Coachs sportifs à {$cityName}",
                'label' => $cityName,
                'intro' => "Découvrez les coachs sportifs à {$cityName} et aux alentours. Comparez leurs spécialités et leurs tarifs, puis contactez directement celui qui correspond à votre objectif — près de chez vous ou en visio.",
            ],
            'related' => $this->relatedLinks(),
            'meta' => $this->buildLandingMeta(
                $request,
                "Coach sportif à {$cityName} | FitnessClic",
                "Trouvez un coach sportif à {$cityName} sur FitnessClic. Comparez les profils, les spécialités et les tarifs, puis contactez votre coach en quelques clics.",
                route('coachs.city', $city),
            ),
        ]);
    }

    /**
     * Page d'atterrissage SEO « Coach {discipline} ».
     */
    public function disciplineLanding(Request $request, string $discipline): Response
    {
        $taxon = CoachTaxonomy::findDiscipline($discipline);

        abort_if($taxon === null, 404);

        $terms = $taxon['terms'];
        $name = $taxon['name'];

        $coaches = $this->baseQuery()
            ->where(function (Builder $query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('specialties', 'like', "%{$term}%")
                        ->orWhere('headline', 'like', "%{$term}%")
                        ->orWhere('bio', 'like', "%{$term}%");
                }
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CoachProfile $p) => $p->toCard());

        return Inertia::render('coachs/Landing', [
            'coaches' => $coaches,
            'landing' => [
                'heading' => "Coachs spécialisés en {$name}",
                'label' => $name,
                'intro' => "Trouvez un coach sportif spécialisé en {$name}. Comparez les profils, les tarifs et les zones d'intervention, puis contactez directement le coach qui vous fera progresser.",
            ],
            'related' => $this->relatedLinks(),
            'meta' => $this->buildLandingMeta(
                $request,
                "Coach {$name} | FitnessClic",
                "Trouvez un coach spécialisé en {$name} sur FitnessClic. Comparez les profils et les tarifs, puis contactez votre coach en quelques clics.",
                route('coachs.discipline', $discipline),
            ),
        ]);
    }

    /**
     * Base query for publicly visible coaches with their card relations.
     */
    private function baseQuery(): Builder
    {
        return CoachProfile::query()
            ->published()
            ->with('user:id,name,avatar_url,created_at')
            ->with('media');
    }

    /**
     * Distinct cities of published coaches (non-empty), as a collection of strings.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function publishedCities()
    {
        return CoachProfile::query()
            ->published()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /**
     * Liens de maillage interne (disciplines + villes) affichés sur les landings.
     *
     * @return array{disciplines: array<int, array{label: string, url: string}>, cities: array<int, array{label: string, url: string}>}
     */
    private function relatedLinks(): array
    {
        $disciplines = array_map(fn (array $d) => [
            'label' => $d['name'],
            'url' => route('coachs.discipline', $d['slug']),
        ], CoachTaxonomy::disciplines());

        $cities = $this->publishedCities()
            ->take(12)
            ->map(fn (string $name) => [
                'label' => $name,
                'url' => route('coachs.city', Str::slug($name)),
            ])
            ->values()
            ->all();

        return ['disciplines' => $disciplines, 'cities' => $cities];
    }

    /**
     * Construit le bloc meta (titre, description, canonical, image) en HTTPS.
     *
     * @return array{title: string, description: string, canonical_url: string, image_url: string}
     */
    private function buildLandingMeta(Request $request, string $title, string $description, string $canonical): array
    {
        $baseUrl = str_replace('http://', 'https://', rtrim($request->getSchemeAndHttpHost(), '/'));

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => str_replace('http://', 'https://', $canonical),
            'image_url' => $baseUrl.'/assets/logo_fitnessclic.png',
        ];
    }

    /**
     * Sanitize the requested radius (km) against the allowed list.
     */
    private function normalizeRadius(mixed $value): int
    {
        $radius = is_numeric($value) ? (int) $value : self::DEFAULT_RADIUS;

        return in_array($radius, self::ALLOWED_RADII, true) ? $radius : self::DEFAULT_RADIUS;
    }

    /**
     * Valide une coordonnée GPS dans sa plage (±90 pour la latitude, ±180 pour
     * la longitude) ; retourne null si absente ou hors limites.
     */
    private function normalizeCoordinate(mixed $value, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        return abs($coordinate) <= $max ? $coordinate : null;
    }

    /**
     * Public coach profile page.
     */
    public function show(Request $request, string $slug)
    {
        $profile = CoachProfile::query()
            ->published()
            ->with('user:id,name,avatar_url,created_at')
            ->with('media')
            ->where('slug', $slug)
            ->firstOrFail();

        $name = $profile->user?->name ?? 'Coach';
        $description = $profile->headline
            ?: \Illuminate\Support\Str::limit(strip_tags((string) $profile->bio), 160)
            ?: "Découvrez le profil de {$name}, coach sportif sur FitnessClic.";

        // URLs absolues en HTTPS pour les crawlers / aperçus sociaux.
        $baseUrl = str_replace('http://', 'https://', rtrim($request->getSchemeAndHttpHost(), '/'));
        $canonicalUrl = $baseUrl."/coachs/{$profile->slug}";
        $imageUrl = $profile->avatar_url ?: $baseUrl.'/assets/logo_fitnessclic.png';
        if (! preg_match('~^https?://~', $imageUrl)) {
            $imageUrl = $baseUrl.'/'.ltrim($imageUrl, '/');
        }
        $imageUrl = str_replace('http://', 'https://', $imageUrl);

        // Les robots des réseaux sociaux reçoivent une vue HTML avec balises OG.
        if ($this->shouldServeShareView($request->userAgent())) {
            return response()->view('coachs.share', [
                'title' => "{$name} — Coach sportif | FitnessClic",
                'description' => $description,
                'imageUrl' => $imageUrl,
                'url' => $canonicalUrl,
            ]);
        }

        return Inertia::render('coachs/Show', [
            'coach' => [
                'slug' => $profile->slug,
                'name' => $profile->user?->name,
                'headline' => $profile->headline,
                'bio' => $profile->bio,
                'hourly_rate' => $profile->hourly_rate_euros,
                'city' => $profile->city,
                'specialties' => $profile->specialties ?? [],
                'avatar_url' => $profile->avatar_url,
                'is_founder' => $profile->is_founder,
            ],
            'meta' => [
                'title' => "{$name} — Coach sportif | FitnessClic",
                'description' => $description,
                'canonical_url' => $canonicalUrl,
                'image_url' => $imageUrl,
            ],
        ]);
    }

    /**
     * Whether the request comes from a social/bot crawler (for OG share view).
     */
    private function shouldServeShareView(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        $bots = [
            'facebookexternalhit', 'facebot', 'twitterbot', 'linkedinbot',
            'slackbot', 'discordbot', 'whatsapp', 'telegrambot',
            'pinterest', 'skypeuripreview', 'googlebot', 'bingbot',
        ];

        $userAgent = strtolower($userAgent);

        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
