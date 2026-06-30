<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConversationsController extends Controller
{
    /**
     * List the current user's conversations.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('messaging/Index', [
            'conversations' => $this->conversationsFor($user),
            'contacts' => $this->contactsFor($user),
        ]);
    }

    /**
     * Build the list of people the user can start a conversation with, drawn
     * from their existing relationships: a client's coaches, a coach's clients
     * (only those linked to a login account, since the others can't be messaged).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function contactsFor(User $user)
    {
        if ($user->isCoach()) {
            return $user->customers()
                ->whereNotNull('account_user_id')
                ->with('accountUser:id,name,avatar_url', 'accountUser.media')
                ->get()
                ->groupBy('account_user_id')
                ->map(function ($records) {
                    /** @var Customer $customer */
                    $customer = $records->first();

                    return [
                        'user_id' => (int) $customer->account_user_id,
                        'name' => $customer->full_name,
                        'avatar' => $this->avatarFor($customer->accountUser),
                    ];
                })
                ->sortBy('name', SORT_FLAG_CASE | SORT_NATURAL)
                ->values();
        }

        if ($user->isClientAccount()) {
            return $user->customerRecords()
                ->whereNotNull('user_id')
                ->with('user:id,name,avatar_url', 'user.coachProfile.media')
                ->get()
                ->groupBy('user_id')
                ->map(function ($records) {
                    /** @var Customer $customer */
                    $coach = $records->first()->user;

                    return [
                        'user_id' => (int) $coach->id,
                        'name' => $coach->name,
                        'avatar' => $this->avatarFor($coach),
                    ];
                })
                ->sortBy('name', SORT_FLAG_CASE | SORT_NATURAL)
                ->values();
        }

        return collect();
    }

    /**
     * Build the conversation list (left pane) for a user.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function conversationsFor(User $user)
    {
        return Conversation::query()
            ->forUser($user)
            ->with([
                'coach:id,name,avatar_url', 'coach.coachProfile.media', 'coach.media',
                'client:id,name,avatar_url', 'client.coachProfile.media', 'client.media',
            ])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')])
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (Conversation $c) use ($user) {
                $other = $c->otherParticipant($user);
                $last = $c->messages->first();

                return [
                    'id' => $c->id,
                    'other_name' => $other?->name,
                    'other_avatar' => $this->avatarFor($other),
                    'last_message' => $last?->body,
                    'last_message_at' => optional($c->last_message_at)->toIso8601String(),
                    'unread_count' => $c->unread_count,
                ];
            });
    }

    /**
     * Resolve a display avatar for a user: coach profile photo if any, else avatar_url.
     */
    private function avatarFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $profile = $user->coachProfile;
        $mediaUrl = $profile
            ? ($profile->getFirstMediaUrl(CoachProfile::MEDIA_AVATAR, 'optimized')
                ?: $profile->getFirstMediaUrl(CoachProfile::MEDIA_AVATAR))
            : null;

        // Photo de profil uploadée par le client — visible uniquement dans la messagerie.
        $mediaUrl = $mediaUrl ?: $user->getMessagingAvatarUrl();

        return $mediaUrl ?: ($user->avatar_url ?: null);
    }

    /**
     * Search the published coach directory so a client can start a conversation
     * with any coach, not only those who already manage them.
     */
    public function searchCoaches(Request $request): JsonResponse
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($me->isClientAccount(), 403);

        $term = trim((string) $request->query('q', ''));

        $coaches = User::query()
            ->where('role', \App\Enums\UserRole::COACH)
            ->whereHas('coachProfile', fn ($q) => $q->where('is_published', true))
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->with('coachProfile.media')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (User $coach) => [
                'user_id' => (int) $coach->id,
                'name' => $coach->name,
                'avatar' => $this->avatarFor($coach),
            ]);

        return response()->json(['coaches' => $coaches]);
    }

    /**
     * Show a conversation thread and mark incoming messages as read.
     */
    public function show(Conversation $conversation): Response
    {
        Gate::authorize('view', $conversation);

        /** @var User $user */
        $user = Auth::user();

        // Mark messages from the other participant as read.
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load([
            'messages.sender:id,name',
            'coach:id,name,avatar_url', 'coach.coachProfile.media', 'coach.media',
            'client:id,name,avatar_url', 'client.coachProfile.media', 'client.media',
        ]);
        $other = $conversation->otherParticipant($user);

        // Le coach peut ajouter son interlocuteur (le client) à sa liste de clients.
        $isCoachOfConversation = $user->id === $conversation->coach_id;
        $alreadyCustomer = $isCoachOfConversation && $other
            ? $this->coachHasCustomerFor($user, $other)
            : false;

        return Inertia::render('messaging/Show', [
            'conversations' => $this->conversationsFor($user),
            'contacts' => $this->contactsFor($user),
            'conversation' => [
                'id' => $conversation->id,
                'other_name' => $other?->name,
                'other_avatar' => $this->avatarFor($other),
                'is_coach' => $isCoachOfConversation,
                'is_customer' => $alreadyCustomer,
            ],
            'messages' => $conversation->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'mine' => $m->sender_id === $user->id,
                'sender_name' => $m->sender?->name,
                'created_at' => $m->created_at->toIso8601String(),
                // Pour mes messages : lu (= "Vu") quand l'autre a ouvert la conversation.
                'read' => $m->read_at !== null,
                'read_at' => optional($m->read_at)->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Start (or reopen) a conversation from a client toward a coach.
     */
    public function start(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isClientAccount(), 403, 'Seuls les clients peuvent contacter un coach.');

        $validated = $request->validate([
            'coach_slug' => ['required', 'string'],
        ]);

        $coach = User::query()
            ->where('role', \App\Enums\UserRole::COACH)
            ->whereHas('coachProfile', fn ($q) => $q->where('slug', $validated['coach_slug']))
            ->firstOrFail();

        $conversation = Conversation::firstOrCreate([
            'coach_id' => $coach->id,
            'client_id' => $user->id,
        ]);

        return redirect()->route('messages.show', $conversation->id);
    }

    /**
     * Start (or reopen) a conversation with a contact picked from the messaging
     * search (a client's coach, or a coach's linked client). The target must
     * belong to the requester's existing relationships.
     */
    public function startWith(User $user, Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = Auth::user();
        $other = $user;

        if ($me->isCoach() && $other->isClientAccount()) {
            abort_unless(
                $me->customers()->where('account_user_id', $other->id)->exists(),
                403,
                'Ce client ne fait pas partie de vos contacts.'
            );
            $coachId = $me->id;
            $clientId = $other->id;
        } elseif ($me->isClientAccount() && $other->isCoach()) {
            // Un client peut contacter n'importe quel coach dont le profil est publié.
            abort_unless(
                $other->coachProfile()->where('is_published', true)->exists(),
                403,
                "Ce coach n'est pas disponible."
            );
            $coachId = $other->id;
            $clientId = $me->id;
        } else {
            abort(403);
        }

        $conversation = Conversation::firstOrCreate([
            'coach_id' => $coachId,
            'client_id' => $clientId,
        ]);

        return redirect()->route('messages.show', $conversation->id);
    }

    /**
     * Start (or reopen) a conversation from a coach toward a customer's linked client account.
     */
    public function startFromCustomer(Customer $customer): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        // Quiconque peut gérer la fiche (propriétaire coach/admin ou membre de l'équipe) peut écrire au client.
        abort_unless($user->canViewCustomer($customer), 403);
        abort_unless($customer->account_user_id !== null, 422, "Ce client n'a pas de compte associé.");

        $conversation = Conversation::firstOrCreate([
            'coach_id' => $user->id,
            'client_id' => $customer->account_user_id,
        ]);

        return redirect()->route('messages.show', $conversation->id);
    }

    /**
     * Add the conversation's client as a customer of the coach (from the messaging header).
     */
    public function addCustomer(Conversation $conversation): RedirectResponse
    {
        Gate::authorize('view', $conversation);

        /** @var User $user */
        $user = Auth::user();

        // Seul le coach de la conversation peut ajouter son interlocuteur comme client.
        $other = $conversation->otherParticipant($user);
        abort_unless($user->id === $conversation->coach_id && $other !== null, 403);

        // La création de clients est réservée aux abonnés Pro.
        if (! $user->can('create', Customer::class)) {
            return back()->with('error', 'La création de clients est réservée aux abonnés Pro. Passez à Pro pour ajouter ce client.');
        }

        // Le client est-il déjà dans la liste du coach ?
        if ($this->coachHasCustomerFor($user, $other)) {
            return back()->with('error', 'Ce client fait déjà partie de votre liste.');
        }

        [$firstName, $lastName] = $this->splitName($other->name);

        Customer::create([
            'user_id' => $user->id,
            'account_user_id' => $other->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $other->email,
            'is_active' => true,
        ]);

        return back()->with('success', $other->name.' a été ajouté à votre liste de clients.');
    }

    /**
     * Whether the coach already has a customer record matching the given user
     * (linked by account or by email).
     */
    private function coachHasCustomerFor(User $coach, User $other): bool
    {
        return $coach->customers()
            ->where(function ($q) use ($other) {
                $q->where('account_user_id', $other->id);

                if ($other->email) {
                    $q->orWhere('email', $other->email);
                }
            })
            ->exists();
    }

    /**
     * Split a single display name into a first and last name for a customer record.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['Client', ''];
        }

        $parts = preg_split('/\s+/', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * Post a new message in a conversation.
     */
    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('reply', $conversation);

        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Throttle email: only notify if the recipient has no pending unread
        // message from this sender yet (avoids one email per message).
        $recipient = $conversation->otherParticipant($user);
        $alreadyUnread = $conversation->messages()
            ->where('sender_id', $user->id)
            ->whereNull('read_at')
            ->exists();

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($recipient && ! $alreadyUnread) {
            $recipient->notify(new NewMessageNotification($message));
        }

        return redirect()->route('messages.show', $conversation->id);
    }

    /**
     * Lightweight unread counter (for polling / badges).
     */
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json(['count' => $user->unreadMessagesCount()]);
    }
}
