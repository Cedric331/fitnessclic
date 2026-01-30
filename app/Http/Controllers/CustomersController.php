<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\IndexCustomerRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(IndexCustomerRequest $request): Response
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = Auth::user();
        $teamMemberIds = $user->teamMemberIds();
        $ownership = $validated['ownership'] ?? 'all';

        $query = Customer::query()
            ->withCount('trainingSessions')
            ->with('user')
            ->whereIn('user_id', $teamMemberIds);

        if (! $user->team_id) {
            $ownership = 'mine';
        }

        if ($ownership === 'mine') {
            $query->where('user_id', $user->id);
        } elseif ($ownership === 'team') {
            $query->where('user_id', '!=', $user->id);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(12);
        $customers->getCollection()->transform(function (Customer $customer) use ($user) {
            $customer->coach_name = $customer->user?->name;
            $customer->is_owner = $customer->user_id === $user->id;

            return $customer;
        });

        return Inertia::render('clients/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $validated['search'] ?? null,
                'ownership' => $ownership,
            ],
        ]);
    }

    /**
     * Create a new customer.
     *
     * @return RedirectResponse
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        if (! $user->can('create', Customer::class)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'La création de clients est réservée aux abonnés Pro. Passez à Pro pour créer des clients illimités.');
        }

        $customer = Customer::create([
            ...$validated,
            'user_id' => Auth::id(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('client.customers.index')
            ->with('success', 'Client créé avec succès.');
    }

    /**
     * Display the customer details.
     */
    public function show(Customer $customer)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $customer->load('user');

        if (! $user || ! $user->canViewCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour voir ce client.');
        }

        $teamMemberIds = $user->teamMemberIds();
        $trainingSessions = $customer->trainingSessions()
            ->whereIn('user_id', $teamMemberIds)
            ->withCount('exercises')
            ->with(['layout', 'user'])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($session) use ($user) {
                $session->has_custom_layout = $session->layout !== null;
                $session->coach_name = $session->user?->name;
                $session->is_owner = $session->user_id === $user->id;

                return $session;
            });

        $customer->setAttribute('training_sessions_count', $trainingSessions->count());
        $customer->setAttribute('coach_name', $customer->user?->name);
        $customer->setAttribute('is_owner', $customer->user_id === $user->id);

        return Inertia::render('clients/Show', [
            'customer' => $customer,
            'training_sessions' => $trainingSessions,
        ]);
    }

    /**
     * Update a customer.
     *
     * @return RedirectResponse
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update($validated);

        return redirect()->route('client.customers.index')
            ->with('success', 'Client modifié avec succès.');
    }

    /**
     * Destroy a customer.
     *
     * @return RedirectResponse
     */
    public function destroy(Customer $customer)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || $customer->user_id !== $user->id) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer ce client.');
        }

        $customer->delete();

        return redirect()->route('client.customers.index')
            ->with('success', 'Client supprimé avec succès.');
    }
}
