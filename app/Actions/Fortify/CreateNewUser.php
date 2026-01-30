<?php

namespace App\Actions\Fortify;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'invite_token' => ['nullable', 'string'],
        ])->validate();

        $invitation = null;
        if (! empty($input['invite_token'])) {
            $invitation = TeamInvitation::query()
                ->where('token', $input['invite_token'])
                ->first();

            if (! $invitation
                || $invitation->isUsed()
                || $invitation->isExpired()
                || $invitation->email !== $input['email']
            ) {
                throw ValidationException::withMessages([
                    'invite_token' => 'Invitation invalide ou expirée.',
                ]);
            }
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        if ($invitation) {
            $user->update(['team_id' => $invitation->team_id]);
            $invitation->update([
                'accepted_at' => now(),
                'invited_user_id' => $user->id,
            ]);
        }

        return $user;
    }
}
