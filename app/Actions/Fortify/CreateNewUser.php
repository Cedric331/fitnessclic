<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Mail\WelcomeEmail;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\CustomerAccountLinker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'max:255',
                (new Email)->validateMxRecord()->preventSpoofing(),
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'invite_token' => ['nullable', 'string'],
            'role' => ['nullable', Rule::in([UserRole::COACH->value, UserRole::CLIENT->value])],
        ])->validate();

        $invitation = null;
        if (! empty($input['invite_token'])) {
            $invitation = TeamInvitation::query()
                ->where('token', $input['invite_token'])
                ->first();

            if (! $invitation || $invitation->isUsed() || $invitation->isExpired()) {
                throw ValidationException::withMessages([
                    'invite_token' => 'Invitation invalide ou expirée.',
                ]);
            }

            if ($invitation->email !== $input['email']) {
                throw ValidationException::withMessages([
                    'email' => 'L\'adresse email ne correspond pas à l\'invitation.',
                ]);
            }
        }

        // An invited user always joins as a coach (team member).
        // Otherwise honor the role chosen at registration, defaulting to coach.
        $role = $invitation
            ? UserRole::COACH
            : (($input['role'] ?? null) === UserRole::CLIENT->value ? UserRole::CLIENT : UserRole::COACH);

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $role,
        ]);

        if ($invitation) {
            $user->teams()->syncWithoutDetaching([$invitation->team_id]);
            $invitation->update([
                'accepted_at' => now(),
                'invited_user_id' => $user->id,
            ]);
        }

        // Link any existing coach-managed customer records sharing this email.
        if ($user->isClientAccount()) {
            app(CustomerAccountLinker::class)->linkUserToCustomers($user);
        }

        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email de bienvenue:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);
        }

        return $user;
    }
}
