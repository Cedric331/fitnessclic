<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientSpaceController extends Controller
{
    /**
     * Client dashboard: courses assigned across all their coaches + their coaches.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isClientAccount(), 403);

        // Customer records (CRM fiches) linked to this client account, across all coaches.
        $records = $user->customerRecords()->with('user.coachProfile')->get();
        $customerIds = $records->pluck('id');

        // Courses (training sessions) assigned to those records.
        $courses = Session::query()
            ->whereHas('customers', fn ($q) => $q->whereIn('customers.id', $customerIds))
            ->with(['user:id,name', 'layout:id,session_id'])
            ->withCount('exercises')
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Session $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'session_date' => optional($s->session_date)->toDateString(),
                'coach_name' => $s->user?->name,
                'exercises_count' => $s->exercises_count,
                'has_custom_layout' => $s->layout !== null,
                'share_url' => route('public.session.show', $s->share_token),
            ]);

        // Distinct coaches behind those records.
        $coaches = $records
            ->map(fn ($r) => $r->user)
            ->filter()
            ->unique('id')
            ->map(fn (User $coach) => [
                'id' => $coach->id,
                'name' => $coach->name,
                'avatar_url' => $coach->coachProfile?->avatar_url,
                'profile_url' => $coach->coachProfile && $coach->coachProfile->is_published
                    ? route('coachs.show', $coach->coachProfile->slug)
                    : null,
            ])
            ->values();

        return Inertia::render('client/Dashboard', [
            'courses' => $courses,
            'coaches' => $coaches,
        ]);
    }
}
