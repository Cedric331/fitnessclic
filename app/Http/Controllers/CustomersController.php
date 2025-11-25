<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $query = Customer::where('user_id', auth()->id())
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
            'is_active' => ['boolean'],
        ]);

        $customer = Customer::create([
            ...$validated,
            'user_id' => auth()->id(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('client.customers.index')
            ->with('success', 'Client créé avec succès.');
    }

    /**
     * Update a customer.
     * @param Request $request
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function update(Request $request, Customer $customer)
    {
        if (!auth()->user()->hasCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour modifier ce client.');
        }
        
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
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
        if (!auth()->user()->hasCustomer($customer)) {
            return redirect()->route('client.customers.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer ce client.');
        }

        $customer->delete();
        
        return redirect()->route('client.customers.index')
            ->with('success', 'Client supprimé avec succès.');
    }
}

