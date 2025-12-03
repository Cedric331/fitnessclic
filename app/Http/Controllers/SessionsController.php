<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Exercise;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SessionEmail;

class SessionsController extends Controller
{
    /**
     * Display a listing of the sessions.
     */
    public function index(Request $request): Response
    {
        $query = Session::where('user_id', Auth::id())
            ->with(['customers', 'exercises.media'])
            ->withCount('exercises');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filtrer par clients via la relation many-to-many
        if ($request->has('customer_id') && $request->customer_id) {
            $query->whereHas('customers', function ($q) use ($request) {
                $q->where('customers.id', $request->customer_id);
            });
        }

        // Gérer le tri par date
        $sortOrder = $request->input('sort', 'newest');
        if ($sortOrder === 'oldest') {
            $query->oldest('session_date')->oldest('created_at');
        } else {
            $query->latest('session_date')->latest('created_at');
        }

        $sessions = $query->paginate(12);

        // Mapper les sessions pour inclure les clients avec is_active
        $sessions->getCollection()->transform(function ($session) {
            $session->customers = $session->customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'full_name' => $customer->full_name,
                    'is_active' => (bool) $customer->is_active, // S'assurer que c'est un booléen
                ];
            });
            return $session;
        });

        $customers = Customer::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('sessions/Index', [
            'sessions' => $sessions,
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_id', 'sort']),
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
                'user_id' => $exercise->user_id,
                'categories' => $exercise->categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                ]),
            ];
        });

        // Récupérer toutes les catégories pour le filtre
        $categories = \App\Models\Category::forUser(Auth::id())
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
            'name' => ['required', 'string', 'max:255'],
            'customer_ids' => ['nullable', 'array'], // Tableau de clients
            'customer_ids.*' => ['required', 'integer', 'exists:customers,id'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'array'], // Séries multiples
            'exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1'],
            'exercises.*.sets.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.sets.*.weight' => ['nullable', 'numeric'],
            'exercises.*.sets.*.rest_time' => ['nullable', 'string'],
            'exercises.*.sets.*.duration' => ['nullable', 'string'],
            'exercises.*.sets.*.order' => ['required', 'integer', 'min:0'],
            // Champs pour compatibilité (si pas de séries multiples)
            'exercises.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.weight' => ['nullable', 'numeric'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
        ]);

        // Récupérer les IDs des clients
        $customerIds = $validated['customer_ids'] ?? [];

        // Vérifier que tous les clients appartiennent à l'utilisateur
        if (!empty($customerIds)) {
            $customers = Customer::whereIn('id', $customerIds)
                ->where('user_id', Auth::id())
                ->get();
            
            if ($customers->count() !== count($customerIds)) {
                abort(403, 'Un ou plusieurs clients ne vous appartiennent pas.');
            }
        }

        $session = Session::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'] ?? 'Nouvelle Séance',
            'notes' => $validated['notes'] ?? null,
            'session_date' => $validated['session_date'] ?? now(),
        ]);

        // Attacher les clients via la relation many-to-many
        if (!empty($customerIds)) {
            $session->customers()->attach($customerIds);
        }

        // Attacher les exercices avec leurs détails
        foreach ($validated['exercises'] as $exerciseData) {
            $sessionExercise = \App\Models\SessionExercise::create([
                'session_id' => $session->id,
                'exercise_id' => $exerciseData['exercise_id'],
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'weight' => $exerciseData['weight'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'additional_description' => $exerciseData['description'] ?? null,
                'order' => $exerciseData['order'],
            ]);

            // Créer les séries multiples si elles existent
            if (isset($exerciseData['sets']) && is_array($exerciseData['sets']) && count($exerciseData['sets']) > 0) {
                foreach ($exerciseData['sets'] as $setData) {
                    \App\Models\SessionExerciseSet::create([
                        'session_exercise_id' => $sessionExercise->id,
                        'set_number' => $setData['set_number'],
                        'repetitions' => $setData['repetitions'] ?? null,
                        'weight' => $setData['weight'] ?? null,
                        'rest_time' => $setData['rest_time'] ?? null,
                        'duration' => $setData['duration'] ?? null,
                        'order' => $setData['order'],
                    ]);
                }
            }
        }

        return redirect()->route('sessions.create')
            ->with('success', 'Séance créée avec succès !');
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

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets'])
            ->loadCount('exercises');

        // Mapper la session avec les données nécessaires pour l'affichage
        $sessionData = [
            'id' => $session->id,
            'name' => $session->name,
            'session_date' => $session->session_date?->format('Y-m-d'),
            'notes' => $session->notes,
            'created_at' => $session->created_at,
            'exercises_count' => $session->exercises_count,
            'customers' => $session->customers->map(fn ($customer) => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'full_name' => $customer->full_name,
            ]),
            'sessionExercises' => $session->sessionExercises->map(function ($se) {
                return [
                    'id' => $se->id,
                    'exercise_id' => $se->exercise_id,
                    'exercise' => $se->exercise ? [
                        'id' => $se->exercise->id,
                        'title' => $se->exercise->title,
                        'description' => $se->exercise->description,
                        'image_url' => $se->exercise->image_url,
                        'suggested_duration' => $se->exercise->suggested_duration,
                        'user_id' => $se->exercise->user_id,
                        'categories' => $se->exercise->categories->map(fn ($cat) => [
                            'id' => $cat->id,
                            'name' => $cat->name,
                        ]),
                    ] : null,
                    'repetitions' => $se->repetitions,
                    'weight' => $se->weight,
                    'rest_time' => $se->rest_time,
                    'duration' => $se->duration,
                    'additional_description' => $se->additional_description,
                    'order' => $se->order,
                    'sets' => $se->sets->map(fn ($set) => [
                        'id' => $set->id,
                        'set_number' => $set->set_number,
                        'repetitions' => $set->repetitions,
                        'weight' => $set->weight,
                        'rest_time' => $set->rest_time,
                        'duration' => $set->duration,
                        'order' => $set->order,
                    ]),
                ];
            }),
        ];

        return Inertia::render('sessions/Show', [
            'session' => $sessionData,
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

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets']);

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
                'user_id' => $exercise->user_id,
                'categories' => $exercise->categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                ]),
            ];
        });

        // Récupérer toutes les catégories pour le filtre
        $categories = \App\Models\Category::forUser(Auth::id())
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

        // Mapper la session avec les données nécessaires
        $sessionData = [
            'id' => $session->id,
            'name' => $session->name,
            'session_date' => $session->session_date?->format('Y-m-d'),
            'notes' => $session->notes,
            'customers' => $session->customers->map(fn ($customer) => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'full_name' => $customer->full_name,
            ]),
            'sessionExercises' => $session->sessionExercises->map(function ($se) {
                return [
                    'id' => $se->id,
                    'exercise_id' => $se->exercise_id,
                    'exercise' => $se->exercise ? [
                        'id' => $se->exercise->id,
                        'title' => $se->exercise->title,
                        'description' => $se->exercise->description,
                        'image_url' => $se->exercise->image_url,
                        'suggested_duration' => $se->exercise->suggested_duration,
                        'user_id' => $se->exercise->user_id,
                        'categories' => $se->exercise->categories->map(fn ($cat) => [
                            'id' => $cat->id,
                            'name' => $cat->name,
                        ]),
                    ] : null,
                    'repetitions' => $se->repetitions,
                    'weight' => $se->weight,
                    'rest_time' => $se->rest_time,
                    'duration' => $se->duration,
                    'additional_description' => $se->additional_description,
                    'order' => $se->order,
                    'sets' => $se->sets->map(fn ($set) => [
                        'id' => $set->id,
                        'set_number' => $set->set_number,
                        'repetitions' => $set->repetitions,
                        'weight' => $set->weight,
                        'rest_time' => $set->rest_time,
                        'duration' => $set->duration,
                        'order' => $set->order,
                    ]),
                ];
            }),
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];

        return Inertia::render('sessions/Edit', [
            'session' => $sessionData,
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
            'customer_ids' => ['nullable', 'array'], // Tableau de clients
            'customer_ids.*' => ['required', 'integer', 'exists:customers,id'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'array'], // Séries multiples
            'exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1'],
            'exercises.*.sets.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.sets.*.weight' => ['nullable', 'numeric'],
            'exercises.*.sets.*.rest_time' => ['nullable', 'string'],
            'exercises.*.sets.*.duration' => ['nullable', 'string'],
            'exercises.*.sets.*.order' => ['required', 'integer', 'min:0'],
            // Champs pour compatibilité (si pas de séries multiples)
            'exercises.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.weight' => ['nullable', 'numeric'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
        ]);

        // Récupérer les IDs des clients
        $customerIds = $validated['customer_ids'] ?? [];

        // Vérifier que tous les clients appartiennent à l'utilisateur
        if (!empty($customerIds)) {
            $customers = Customer::whereIn('id', $customerIds)
                ->where('user_id', Auth::id())
                ->get();
            
            if ($customers->count() !== count($customerIds)) {
                abort(403, 'Un ou plusieurs clients ne vous appartiennent pas.');
            }
        }

        $session->update([
            'name' => $validated['name'] ?? $session->name,
            'notes' => $validated['notes'] ?? $session->notes,
            'session_date' => $validated['session_date'] ?? $session->session_date,
        ]);

        // Synchroniser les clients via la relation many-to-many
        if (isset($validated['customer_ids'])) {
            $session->customers()->sync($customerIds);
        }

        // Synchroniser les exercices
        // Supprimer tous les exercices existants (cela supprimera aussi les sets via cascade)
        $session->sessionExercises()->delete();
        
        foreach ($validated['exercises'] as $exerciseData) {
            $sessionExercise = \App\Models\SessionExercise::create([
                'session_id' => $session->id,
                'exercise_id' => $exerciseData['exercise_id'],
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'weight' => $exerciseData['weight'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'additional_description' => $exerciseData['description'] ?? null,
                'order' => $exerciseData['order'],
            ]);

            // Créer les séries multiples si elles existent
            if (isset($exerciseData['sets']) && is_array($exerciseData['sets']) && count($exerciseData['sets']) > 0) {
                foreach ($exerciseData['sets'] as $setData) {
                    \App\Models\SessionExerciseSet::create([
                        'session_exercise_id' => $sessionExercise->id,
                        'set_number' => $setData['set_number'],
                        'repetitions' => $setData['repetitions'] ?? null,
                        'weight' => $setData['weight'] ?? null,
                        'rest_time' => $setData['rest_time'] ?? null,
                        'duration' => $setData['duration'] ?? null,
                        'order' => $setData['order'],
                    ]);
                }
            }
        }

        return redirect()->route('sessions.edit', $session)
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
    /**
     * Generate PDF for a saved session.
     */
    public function pdf(Session $session)
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets', 'user']);

        $pdf = Pdf::loadView('sessions.pdf', [
            'session' => $session,
        ]);
        
        $fileName = $session->name ?: "seance-{$session->id}";
        $fileName = Str::slug($fileName) . '.pdf';
        
        return $pdf->download($fileName);
    }

    /**
     * Generate PDF from unsaved session data (from create page).
     */
    public function pdfPreview(Request $request)
    {
        try {
            // Si la requête est en JSON, décoder les données
            if ($request->isJson() || $request->header('Content-Type') === 'application/json') {
                $request->merge(json_decode($request->getContent(), true) ?? []);
            }
            
            $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.sets' => ['nullable', 'array'],
            'exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1'],
            'exercises.*.sets.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.sets.*.weight' => ['nullable', 'numeric'],
            'exercises.*.sets.*.rest_time' => ['nullable', 'string'],
            'exercises.*.sets.*.duration' => ['nullable', 'string'],
            'exercises.*.sets.*.order' => ['required', 'integer', 'min:0'],
            'exercises.*.repetitions' => ['nullable', 'integer'],
            'exercises.*.weight' => ['nullable', 'numeric'],
            'exercises.*.rest_time' => ['nullable', 'string'],
            'exercises.*.duration' => ['nullable', 'string'],
            'exercises.*.description' => ['nullable', 'string'],
            'exercises.*.order' => ['required', 'integer', 'min:0'],
        ]);

        // Charger les exercices avec leurs relations
        $exerciseIds = array_column($validated['exercises'], 'exercise_id');
        $exercises = Exercise::whereIn('id', $exerciseIds)
            ->with(['categories', 'media'])
            ->get()
            ->keyBy('id');

        // Construire les données de session pour la vue
        $sessionData = [
            'name' => $validated['name'] ?? 'Nouvelle Séance',
            'session_date' => $validated['session_date'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'user' => Auth::user(),
            'sessionExercises' => collect($validated['exercises'])->map(function ($exerciseData) use ($exercises) {
                $exercise = $exercises->get($exerciseData['exercise_id']);
                if (!$exercise) {
                    return null;
                }

                return (object) [
                    'exercise' => $exercise,
                    'sets' => isset($exerciseData['sets']) && is_array($exerciseData['sets']) 
                        ? collect($exerciseData['sets'])->map(fn($set) => (object) $set)->sortBy('order')
                        : collect([(object) [
                            'set_number' => 1,
                            'repetitions' => $exerciseData['repetitions'] ?? null,
                            'weight' => $exerciseData['weight'] ?? null,
                            'rest_time' => $exerciseData['rest_time'] ?? null,
                            'duration' => $exerciseData['duration'] ?? null,
                            'order' => 0,
                        ]]),
                    'additional_description' => $exerciseData['description'] ?? null,
                    'order' => $exerciseData['order'] ?? 0,
                ];
            })->filter()->sortBy('order')->values(),
        ];

            $pdf = Pdf::loadView('sessions.pdf', [
                'session' => (object) $sessionData,
            ]);
            
            $fileName = $sessionData['name'] ? Str::slug($sessionData['name']) : 'nouvelle-seance';
            $fileName .= '.pdf';
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send session by email to a customer.
     */
    public function sendEmail(Request $request, Session $session): RedirectResponse
    {
        // Vérifier que la séance appartient à l'utilisateur
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        // Charger la session avec les relations nécessaires
        $session->load(['customers', 'user']);

        // Vérifier que le client est associé à la séance
        $customer = Customer::where('id', $validated['customer_id'])
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            return redirect()->route('sessions.index')
                ->with('error', 'Client non trouvé ou inactif.');
        }

        // Vérifier que le client est bien associé à cette séance
        if (!$session->customers->contains($customer->id)) {
            return redirect()->route('sessions.index')
                ->with('error', 'Ce client n\'est pas associé à cette séance.');
        }

        // Vérifier que le client a une adresse email
        if (!$customer->email) {
            return redirect()->route('sessions.index')
                ->with('error', 'Ce client n\'a pas d\'adresse email.');
        }

        try {
            // Envoyer l'email
            Mail::to($customer->email)->send(new SessionEmail($session, $customer));

            return redirect()->route('sessions.index')
                ->with('success', "La séance a été envoyée par email à {$customer->full_name}.");
        } catch (\Exception $e) {
            Log::error('Erreur envoi email séance:', [
                'session_id' => $session->id,
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('sessions.index')
                ->with('error', 'Une erreur est survenue lors de l\'envoi de l\'email.');
        }
    }
}

