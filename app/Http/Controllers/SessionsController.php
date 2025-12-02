<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Exercise;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SessionsController extends Controller
{
    /**
     * Display a listing of the sessions.
     */
    public function index(Request $request): Response
    {
        $query = Session::where('user_id', Auth::id())
            ->with(['customer', 'exercises'])
            ->withCount('exercises');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        $sessions = $query->latest('session_date')->latest('created_at')->paginate(12);

        $customers = Customer::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('sessions/Index', [
            'sessions' => $sessions,
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_id']),
        ]);
    }

    /**
     * Show the form for creating a new session.
     */
    public function create(Request $request): Response
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');

        // Récupérer tous les exercices disponibles
        $exercisesQuery = Exercise::query()
            ->where('is_shared', true)
            ->with(['categories', 'media'])
            ->when($searchTerm !== '', fn ($query) => $query->where('title', 'like', "%{$searchTerm}%"))
            ->when($categoryId, fn ($query, $value) => $query->whereHas('categories', fn ($query) => $query->where('categories.id', $value)));

        $exercises = $exercisesQuery->orderBy('title')->get()->map(function ($exercise) {
            return [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'description' => $exercise->description,
                'image_url' => $exercise->image_url,
                'suggested_duration' => $exercise->suggested_duration,
                'categories' => $exercise->categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                ]),
            ];
        });

        // Récupérer toutes les catégories pour le filtre
        $categories = \App\Models\Category::public()
            ->orderBy('name')
            ->get();

        // Récupérer les clients actifs avec full_name
        $customers = Customer::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'full_name' => $customer->full_name,
                ];
            });

        return Inertia::render('sessions/Create', [
            'exercises' => $exercises,
            'categories' => $categories,
            'customers' => $customers,
            'filters' => [
                'search' => $searchTerm ?: null,
                'category_id' => $categoryId ? (int) $categoryId : null,
            ],
        ]);
    }

    /**
     * Store a newly created session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'person_name' => ['nullable', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'integer', 'min:1'],
            'exercises.*.repetitions' => ['nullable', 'string'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
        ]);

        // Vérifier que le client appartient à l'utilisateur si fourni
        if (isset($validated['customer_id'])) {
            $customer = Customer::where('id', $validated['customer_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();
        }

        $session = Session::create([
            'user_id' => Auth::id(),
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'] ?? 'Nouvelle Séance',
            'notes' => $validated['notes'] ?? null,
            'session_date' => $validated['session_date'] ?? now(),
        ]);

        // Attacher les exercices avec leurs détails
        foreach ($validated['exercises'] as $exerciseData) {
            $session->exercises()->attach($exerciseData['exercise_id'], [
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'additional_description' => $exerciseData['description'] ?? null,
                'order' => $exerciseData['order'],
            ]);
        }

        return redirect()->route('sessions.index')
            ->with('success', 'Séance créée avec succès.');
    }

    /**
     * Display the specified session.
     */
    public function show(Session $session): Response
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['customer', 'exercises.categories', 'exercises.media']);

        return Inertia::render('sessions/Show', [
            'session' => $session,
        ]);
    }

    /**
     * Show the form for editing the specified session.
     */
    public function edit(Session $session, Request $request): Response
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['customer', 'exercises.categories', 'exercises.media']);

        $searchTerm = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');

        // Récupérer tous les exercices disponibles
        $exercisesQuery = Exercise::query()
            ->where('is_shared', true)
            ->with(['categories', 'media'])
            ->when($searchTerm !== '', fn ($query) => $query->where('title', 'like', "%{$searchTerm}%"))
            ->when($categoryId, fn ($query, $value) => $query->whereHas('categories', fn ($query) => $query->where('categories.id', $value)));

        $exercises = $exercisesQuery->orderBy('title')->get();

        // Récupérer toutes les catégories pour le filtre
        $categories = \App\Models\Category::public()
            ->orderBy('name')
            ->get();

        // Récupérer les clients actifs avec full_name
        $customers = Customer::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'full_name' => $customer->full_name,
                ];
            });

        return Inertia::render('sessions/Edit', [
            'session' => $session,
            'exercises' => $exercises,
            'categories' => $categories,
            'customers' => $customers,
            'filters' => [
                'search' => $searchTerm ?: null,
                'category_id' => $categoryId ? (int) $categoryId : null,
            ],
        ]);
    }

    /**
     * Update the specified session.
     */
    public function update(Request $request, Session $session): RedirectResponse
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'person_name' => ['nullable', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'integer', 'min:1'],
            'exercises.*.repetitions' => ['nullable', 'string'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
        ]);

        // Vérifier que le client appartient à l'utilisateur si fourni
        if (isset($validated['customer_id'])) {
            $customer = Customer::where('id', $validated['customer_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();
        }

        $session->update([
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'] ?? 'Nouvelle Séance',
            'notes' => $validated['notes'] ?? null,
            'session_date' => $validated['session_date'] ?? $session->session_date,
        ]);

        // Synchroniser les exercices
        $session->exercises()->detach();
        foreach ($validated['exercises'] as $exerciseData) {
            $session->exercises()->attach($exerciseData['exercise_id'], [
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'additional_description' => $exerciseData['description'] ?? null,
                'order' => $exerciseData['order'],
            ]);
        }

        return redirect()->route('sessions.index')
            ->with('success', 'Séance mise à jour avec succès.');
    }

    /**
     * Remove the specified session.
     */
    public function destroy(Session $session): RedirectResponse
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->delete();

        return redirect()->route('sessions.index')
            ->with('success', 'Séance supprimée avec succès.');
    }

    /**
     * Generate PDF for the session.
     */
    public function pdf(Session $session)
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['customer', 'exercises.categories', 'exercises.media', 'user']);

        $pdf = \PDF::loadView('sessions.pdf', [
            'session' => $session,
        ]);
        
        return $pdf->download("seance-{$session->id}.pdf");
    }
}

