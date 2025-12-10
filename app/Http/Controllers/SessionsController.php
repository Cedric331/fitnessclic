<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sessions\IndexSessionRequest;
use App\Http\Requests\Sessions\PdfPreviewSessionRequest;
use App\Http\Requests\Sessions\SendEmailSessionRequest;
use App\Http\Requests\Sessions\StoreSessionRequest;
use App\Http\Requests\Sessions\UpdateSessionRequest;
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
    public function index(IndexSessionRequest $request): Response
    {
        $validated = $request->validated();
        
        $query = Session::where('user_id', Auth::id())
            ->with(['customers', 'exercises.media'])
            ->withCount('exercises');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['customer_id'])) {
            $query->whereHas('customers', function ($q) use ($validated) {
                $q->where('customers.id', $validated['customer_id']);
            });
        }

        $sortOrder = $validated['sort'] ?? 'newest';
        if ($sortOrder === 'oldest') {
            $query->oldest('session_date')->oldest('created_at');
        } else {
            $query->latest('session_date')->latest('created_at');
        }

        $sessions = $query->paginate(12);

        $sessions->getCollection()->transform(function ($session) {
            $session->customers = $session->customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'full_name' => $customer->full_name,
                    'is_active' => (bool) $customer->is_active,
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

        $categories = \App\Models\Category::forUser(Auth::id())
            ->orderBy('name')
            ->get();

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
    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        if (!$user->canSaveSessions()) {
            return redirect()->route('sessions.create')
                ->with('error', 'L\'enregistrement des séances est réservé aux abonnés Pro. Passez à Pro pour enregistrer vos séances.');
        }

        $customerIds = $validated['customer_ids'] ?? [];

        $session = Session::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'] ?? 'Nouvelle Séance',
            'notes' => $validated['notes'] ?? null,
            'session_date' => $validated['session_date'] ?? now(),
        ]);

        if (!empty($customerIds)) {
            $session->customers()->attach($customerIds);
        }

        foreach ($validated['exercises'] as $exerciseData) {
            $sessionExercise = \App\Models\SessionExercise::create([
                'session_id' => $session->id,
                'exercise_id' => $exerciseData['exercise_id'],
                'custom_exercise_name' => $exerciseData['custom_exercise_name'] ?? null,
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'weight' => $exerciseData['weight'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'use_duration' => $exerciseData['use_duration'] ?? false,
                'use_bodyweight' => $exerciseData['use_bodyweight'] ?? false,
                'additional_description' => $exerciseData['description'] ?? null,
                'sets_count' => $exerciseData['sets_count'] ?? null,
                'order' => $exerciseData['order'],
                'block_id' => $exerciseData['block_id'] ?? null,
                'block_type' => $exerciseData['block_type'] ?? null,
                'position_in_block' => $exerciseData['position_in_block'] ?? null,
            ]);

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
        if ($session->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets'])
            ->loadCount('exercises');

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
                    'custom_exercise_name' => $se->custom_exercise_name,
                    'repetitions' => $se->repetitions,
                    'weight' => $se->weight,
                    'rest_time' => $se->rest_time,
                    'duration' => $se->duration,
                    'use_duration' => $se->use_duration ?? false,
                    'use_bodyweight' => $se->use_bodyweight ?? false,
                    'additional_description' => $se->additional_description,
                    'sets_count' => $se->sets_count,
                    'order' => $se->order,
                    'block_id' => $se->block_id,
                    'block_type' => $se->block_type,
                    'position_in_block' => $se->position_in_block,
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
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets']);

        $searchTerm = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');

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

        $categories = \App\Models\Category::forUser(Auth::id())
            ->orderBy('name')
            ->get();

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
                    'custom_exercise_name' => $se->custom_exercise_name,
                    'repetitions' => $se->repetitions,
                    'weight' => $se->weight,
                    'rest_time' => $se->rest_time,
                    'duration' => $se->duration,
                    'use_duration' => $se->use_duration ?? false,
                    'use_bodyweight' => $se->use_bodyweight ?? false,
                    'additional_description' => $se->additional_description,
                    'sets_count' => $se->sets_count,
                    'order' => $se->order,
                    'block_id' => $se->block_id ?? null,
                    'block_type' => $se->block_type ?? null,
                    'position_in_block' => $se->position_in_block ?? null,
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
    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $validated = $request->validated();

        $customerIds = $validated['customer_ids'] ?? [];

        $session->update([
            'name' => $validated['name'] ?? $session->name,
            'notes' => $validated['notes'] ?? $session->notes,
            'session_date' => $validated['session_date'] ?? $session->session_date,
        ]);

        if (isset($validated['customer_ids'])) {
            $session->customers()->sync($customerIds);
        }

        $session->sessionExercises()->delete();
        
        foreach ($validated['exercises'] as $exerciseData) {
            $sessionExercise = \App\Models\SessionExercise::create([
                'session_id' => $session->id,
                'exercise_id' => $exerciseData['exercise_id'],
                'custom_exercise_name' => $exerciseData['custom_exercise_name'] ?? null,
                'repetitions' => $exerciseData['repetitions'] ?? null,
                'weight' => $exerciseData['weight'] ?? null,
                'rest_time' => $exerciseData['rest_time'] ?? null,
                'duration' => $exerciseData['duration'] ?? null,
                'use_duration' => $exerciseData['use_duration'] ?? false,
                'use_bodyweight' => $exerciseData['use_bodyweight'] ?? false,
                'additional_description' => $exerciseData['description'] ?? null,
                'sets_count' => $exerciseData['sets_count'] ?? null,
                'order' => $exerciseData['order'],
                'block_id' => $exerciseData['block_id'] ?? null,
                'block_type' => $exerciseData['block_type'] ?? null,
                'position_in_block' => $exerciseData['position_in_block'] ?? null,
            ]);

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
    public function pdf(Session $session, Request $request)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        if (!$user->canExportPdf()) {
            return redirect()->route('sessions.show', $session)
                ->with('error', 'L\'export PDF est réservé aux abonnés Pro. Passez à Pro pour exporter vos séances en PDF.');
        }

        $session->load(['customers', 'sessionExercises.exercise.categories', 'sessionExercises.exercise.media', 'sessionExercises.sets', 'user']);

        $pdf = Pdf::loadView('sessions.pdf', [
            'session' => $session,
        ])->setOption('enable-local-file-access', true);
        
        $fileName = $session->name ?: "seance-{$session->id}";
        $fileName = Str::slug($fileName) . '.pdf';
        
        if ($request->ajax() || $request->wantsJson()) {
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        }
        
        return $pdf->download($fileName);
    }

    /**
     * Generate PDF from unsaved session data (from create page).
     */
    public function pdfPreview(PdfPreviewSessionRequest $request)
    {
        $user = Auth::user();
        if (!$user->canExportPdf()) {
            return response()->json([
                'error' => 'L\'export PDF est réservé aux abonnés Pro. Passez à Pro pour exporter vos séances en PDF.'
            ], 403);
        }

        try {
            $validated = $request->validated();

        $exerciseIds = array_column($validated['exercises'], 'exercise_id');
        $exercises = Exercise::whereIn('id', $exerciseIds)
            ->with(['categories', 'media'])
            ->get()
            ->keyBy('id');

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
                    'custom_exercise_name' => $exerciseData['custom_exercise_name'] ?? null,
                    'use_duration' => $exerciseData['use_duration'] ?? false,
                    'use_bodyweight' => $exerciseData['use_bodyweight'] ?? false,
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
                    'description' => $exerciseData['description'] ?? null,
                    'sets_count' => $exerciseData['sets_count'] ?? null,
                    'order' => $exerciseData['order'] ?? 0,
                    'block_id' => $exerciseData['block_id'] ?? null,
                    'block_type' => $exerciseData['block_type'] ?? null,
                    'position_in_block' => $exerciseData['position_in_block'] ?? null,
                ];
            })->filter()->sortBy('order')->values(),
        ];

            $pdf = Pdf::loadView('sessions.pdf', [
                'session' => (object) $sessionData,
            ])->setOption('enable-local-file-access', true);
            
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
    public function sendEmail(SendEmailSessionRequest $request, Session $session): RedirectResponse
    {
        $validated = $request->validated();

        $session->load(['customers', 'user']);

        $customer = Customer::where('id', $validated['customer_id'])
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        $redirectRoute = $validated['redirect_to_customer'] ?? false
            ? 'client.customers.show'
            : 'sessions.index';
        $redirectParams = $validated['redirect_to_customer'] ?? false
            ? ['customer' => $validated['customer_id']]
            : [];

        try {
            Mail::to($customer->email)->send(new SessionEmail($session, $customer));

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('success', "La séance a été envoyée par email à {$customer->full_name}.");
        } catch (\Exception $e) {
            Log::error('Erreur envoi email séance:', [
                'session_id' => $session->id,
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'Une erreur est survenue lors de l\'envoi de l\'email.');
        }
    }
}

