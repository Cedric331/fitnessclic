<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Centralizes the linking of coach-managed customer records (`customers`)
 * to client login accounts (`users` with role `client`), keyed by email.
 *
 * See specs.md §3bis.
 */
class CustomerAccountLinker
{
    /**
     * Normalize an email for comparison.
     */
    public static function normalize(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : mb_strtolower($email);
    }

    /**
     * Link every unlinked customer record matching the user's email to that
     * client account. Called when a client signs up.
     *
     * @return int Number of records linked.
     */
    public function linkUserToCustomers(User $user): int
    {
        if (! $user->isClientAccount()) {
            return 0;
        }

        $email = self::normalize($user->email);

        if ($email === null) {
            return 0;
        }

        return Customer::query()
            ->whereNull('account_user_id')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->update(['account_user_id' => $user->id]);
    }

    /**
     * Resolve and set the linked client account for a customer record, by email.
     * Called when a coach creates/updates a customer record.
     *
     * @return int|null The linked user id, or null if no match.
     */
    public function resolveAccountIdForCustomer(Customer $customer): ?int
    {
        $email = self::normalize($customer->email);

        if ($email === null) {
            return null;
        }

        return User::query()
            ->where('role', UserRole::CLIENT)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->value('id');
    }
}
