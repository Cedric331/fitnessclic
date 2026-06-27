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
        ]);
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
                'coach:id,name,avatar_url', 'coach.coachProfile.media',
                'client:id,name,avatar_url', 'client.coachProfile.media',
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

        return $mediaUrl ?: ($user->avatar_url ?: null);
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
            'coach:id,name,avatar_url', 'coach.coachProfile.media',
            'client:id,name,avatar_url', 'client.coachProfile.media',
        ]);
        $other = $conversation->otherParticipant($user);

        return Inertia::render('messaging/Show', [
            'conversations' => $this->conversationsFor($user),
            'conversation' => [
                'id' => $conversation->id,
                'other_name' => $other?->name,
                'other_avatar' => $this->avatarFor($other),
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
