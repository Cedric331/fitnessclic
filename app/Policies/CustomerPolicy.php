<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine if the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->isPro();
    }

    /**
     * Determine if the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id;
    }

    /**
     * Determine if the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id && $user->isPro();
    }

    /**
     * Determine if the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id && $user->isPro();
    }
}
