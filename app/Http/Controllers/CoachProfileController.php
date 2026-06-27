<?php

namespace App\Http\Controllers;

use App\Http\Requests\Coach\UpdateCoachProfileRequest;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CoachProfileController extends Controller
{
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

        $profile->fill([
            'headline' => $validated['headline'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'hourly_rate' => isset($validated['hourly_rate'])
                ? (int) round((float) $validated['hourly_rate'] * 100)
                : null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'specialties' => $this->parseSpecialties($validated['specialties'] ?? null),
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
            'is_published' => $profile->is_published,
            'avatar_url' => $profile->avatar_url,
            'public_url' => route('coachs.show', $profile->slug),
        ];
    }
}
