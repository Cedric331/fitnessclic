<?php

namespace App\Http\Controllers;

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
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Customer::where('user_id', Auth::id())
            ->withCount('trainingSessions');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
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
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

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
     * @param Request $request
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function update(Request $request, Customer $customer)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour modifier ce client.');
        }
        
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
        
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

