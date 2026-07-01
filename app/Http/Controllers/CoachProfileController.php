<?php

namespace App\Http\Controllers;

use App\Enums\CoachingMode;
use App\Http\Requests\Coach\UpdateCoachProfileRequest;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CoachProfileController extends Controller
{
    public function __construct(private readonly GeocodingService $geocoder) {}

    /**
     * Show the coach's own profile edit form.
     */
    public function edit(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isCoach(), 403);

        $profile = CoachProfile::firstOrCreate(['user_id' => $user->id]);

        return Inertia::render('coach/ProfileEdit', [
            'profile' => $this->present($profile),
        ]);
    }

    /**
     * Update the coach's own profile.
     */
    public function update(UpdateCoachProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = CoachProfile::firstOrCreate(['user_id' => $user->id]);
        Gate::authorize('update', $profile);

        $validated = $request->validated();

        $city = $validated['city'] ?? null;
        $postalCode = $validated['postal_code'] ?? null;

        // Coordonnées fournies par l'autocomplétion ; sinon géocodage de secours
        // (ville saisie manuellement) tout en préservant les coordonnées
        // existantes lorsque la ville n'a pas changé.
        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        if (! $city) {
            $latitude = null;
            $longitude = null;
        } elseif ($latitude === null || $longitude === null) {
            $cityChanged = $city !== $profile->city || $postalCode !== $profile->postal_code;

            if ($cityChanged || $profile->latitude === null) {
                $coordinates = $this->geocoder->geocodeCity($city, $postalCode);
                $latitude = $coordinates['lat'] ?? null;
                $longitude = $coordinates['lng'] ?? null;
            } else {
                $latitude = $profile->latitude;
                $longitude = $profile->longitude;
            }
        }

        $profile->fill([
            'headline' => $validated['headline'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'hourly_rate' => isset($validated['hourly_rate'])
                ? (int) round((float) $validated['hourly_rate'] * 100)
                : null,
            'city' => $city,
            'postal_code' => $postalCode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'specialties' => $this->parseSpecialties($validated['specialties'] ?? null),
            'coaching_mode' => $validated['coaching_mode'] ?? CoachingMode::InPerson->value,
            'is_published' => $request->boolean('is_published'),
        ]);
        $profile->save();

        if ($request->hasFile('photo')) {
            $profile->addMediaFromRequest('photo')
                ->toMediaCollection(CoachProfile::MEDIA_AVATAR);
        }

        return redirect()->route('coach.profile.edit')
            ->with('success', 'Votre profil a été mis à jour.');
    }

    /**
     * @return array<int, string>
     */
    private function parseSpecialties(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(CoachProfile $profile): array
    {
        return [
            'slug' => $profile->slug,
            'headline' => $profile->headline,
            'bio' => $profile->bio,
            'hourly_rate' => $profile->hourly_rate_euros,
            'city' => $profile->city,
            'postal_code' => $profile->postal_code,
            'specialties' => implode(', ', $profile->specialties ?? []),
            'coaching_mode' => ($profile->coaching_mode ?? CoachingMode::InPerson)->value,
            'is_published' => $profile->is_published,
            'avatar_url' => $profile->avatar_url,
            'public_url' => route('coachs.show', $profile->slug),
            'completion' => $profile->completion(),
        ];
    }
}
