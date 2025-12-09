<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\IndexCustomerRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
{
    /**
     * Display a listing of the customers.
     * @param IndexCustomerRequest $request
     * @return Response
     */
    public function index(IndexCustomerRequest $request): Response
    {
        $validated = $request->validated();
        
        $query = Customer::where('user_id', Auth::id())
            ->withCount('trainingSessions');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(12);

        return Inertia::render('clients/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Create a new customer.
     * @param StoreCustomerRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Les comptes gratuits ne peuvent pas créer de clients
        if ($user->isFree()) {
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

        if (!$user || !$user->hasCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour voir ce client.');
        }

        $trainingSessions = $customer->trainingSessions()
            ->where('user_id', $user->id)
            ->withCount('exercises')
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get();

        $customer->setAttribute('training_sessions_count', $trainingSessions->count());

        return Inertia::render('clients/Show', [
            'customer' => $customer,
            'training_sessions' => $trainingSessions,
        ]);
    }

    /**
     * Update a customer.
     * @param UpdateCustomerRequest $request
     * @param Customer $customer
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
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function destroy(Customer $customer)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer ce client.');
        }

        $customer->delete();
        
        return redirect()->route('client.customers.index')
            ->with('success', 'Client supprimé avec succès.');
    }
}

