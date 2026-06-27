<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoachDirectoryController extends Controller
{
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
        ];

        // Tarif saisi en euros → comparé aux centimes stockés.
        $minCents = is_numeric($filters['min_rate']) ? (int) round((float) $filters['min_rate'] * 100) : null;
        $maxCents = is_numeric($filters['max_rate']) ? (int) round((float) $filters['max_rate'] * 100) : null;

        $coaches = CoachProfile::query()
            ->published()
            ->with('user:id,name,avatar_url')
            ->with('media')
            ->when($filters['city'] !== '', fn ($q) => $q->where('city', 'like', "%{$filters['city']}%"))
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
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CoachProfile $p) => $this->card($p));

        return Inertia::render('coachs/Index', [
            'coaches' => $coaches,
            'filters' => $filters,
        ]);
    }

    /**
     * Public coach profile page.
     */
    public function show(Request $request, string $slug)
    {
        $profile = CoachProfile::query()
            ->published()
            ->with('user:id,name,avatar_url')
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

    /**
     * @return array<string, mixed>
     */
    private function card(CoachProfile $profile): array
    {
        return [
            'slug' => $profile->slug,
            'name' => $profile->user?->name,
            'headline' => $profile->headline,
            'hourly_rate' => $profile->hourly_rate_euros,
            'city' => $profile->city,
            'specialties' => array_slice($profile->specialties ?? [], 0, 3),
            'avatar_url' => $profile->avatar_url,
        ];
    }
}
