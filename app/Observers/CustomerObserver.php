<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\CustomerAccountLinker;

class CustomerObserver
{
    public function __construct(private CustomerAccountLinker $linker) {}

    /**
     * Before creating a record, resolve its linked client account by email.
     */
    public function creating(Customer $customer): void
    {
        if ($customer->account_user_id === null) {
            $customer->account_user_id = $this->linker->resolveAccountIdForCustomer($customer);
        }
    }

    /**
     * When the email changes, re-resolve the linked client account.
     */
    public function updating(Customer $customer): void
    {
        if ($customer->isDirty('email')) {
            $customer->account_user_id = $this->linker->resolveAccountIdForCustomer($customer);
        }
    }
}
