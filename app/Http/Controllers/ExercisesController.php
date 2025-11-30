<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExercisesController extends Controller
{
    /**
     * Display the exercises library.
     */
    public function index(Request $request): Response
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');
        $sortOrder = $request->input('sort', 'newest');
        $viewMode = $request->input('view', 'grid-4');

        $exercisesQuery = Exercise::availableForUser(Auth::id())
            ->with('categories')
            ->when($searchTerm !== '', fn ($query) => $query->where('title', 'like', "%{$searchTerm}%"))
            ->when($categoryId, fn ($query, $value) => $query->whereHas('categories', fn ($query) => $query->where('categories.id', $value)));

        if ($sortOrder === 'oldest') {
            $exercisesQuery->orderBy('created_at', 'asc');
        } else {
            $exercisesQuery->orderBy('created_at', 'desc');
        }

        $perPage = 24;
        $exercises = $exercisesQuery->paginate($perPage);

        // Récupérer les IDs des exercices publics que l'utilisateur a déjà importés
        // (même titre et description)
        $userOwnedExercises = Exercise::where('user_id', Auth::id())
            ->get(['title', 'description']);
        
        $importedPublicExerciseIds = [];
        if ($userOwnedExercises->isNotEmpty()) {
            $publicExercises = Exercise::shared()
                ->where('user_id', '!=', Auth::id())
                ->get(['id', 'title', 'description']);
            
            foreach ($publicExercises as $publicExercise) {
                foreach ($userOwnedExercises as $userExercise) {
                    if ($publicExercise->title === $userExercise->title 
                        && $publicExercise->description === $userExercise->description) {
                        $importedPublicExerciseIds[] = $publicExercise->id;
                        break;
                    }
                }
            }
        }

        return Inertia::render('exercises/Index', [
            'exercises' => [
                'data' => $exercises->map(fn (Exercise $exercise) => [
                    'id' => $exercise->id,
                    'name' => $exercise->title,
                    'image_url' => $exercise->image_url,
                    'category_name' => $exercise->categories->isNotEmpty() 
                        ? $exercise->categories->pluck('name')->join(', ') 
                        : 'Sans catégorie',
                    'categories' => $exercise->categories->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]),
                    'created_at' => optional($exercise->created_at)->toDateTimeString(),
                ]),
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
                'has_more' => $exercises->hasMorePages(),
            ],
            'filters' => [
                'search' => $searchTerm ?: null,
                'category_id' => $categoryId ? (int) $categoryId : null,
                'sort' => in_array($sortOrder, ['newest', 'oldest'], true) ? $sortOrder : 'newest',
                'view' => in_array($viewMode, ['grid-2', 'grid-4', 'grid-6', 'grid-8'], true) ? $viewMode : 'grid-4',
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'imported_public_exercise_ids' => $importedPublicExerciseIds,
        ]);
    }

    /**
     * Store a newly created exercise.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'suggested_duration' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'exists:categories,id'],
            'image' => ['required', 'image', 'max:5120'], // 5MB max
            'is_shared' => ['boolean'],
        ]);

        $exercise = Exercise::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => $validated['is_shared'] ?? false,
        ]);

        // Attacher les catégories
        $exercise->categories()->attach($validated['category_ids']);

        // Ajouter l'image
        if ($request->hasFile('image')) {
            $exercise->addMediaFromRequest('image')
                ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);
        }

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice créé avec succès.');
    }

    /**
     * Update the specified exercise.
     */
    public function update(Request $request, Exercise $exercise)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut modifier cet exercice
        if ($exercise->user_id !== Auth::id() && (!$user || !$user->isAdmin())) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous n\'avez pas les permissions pour modifier cet exercice.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'suggested_duration' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max, nullable pour l'édition
            'is_shared' => ['boolean'],
        ]);

        $exercise->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => $validated['is_shared'] ?? false,
        ]);

        // Mettre à jour les catégories
        $exercise->categories()->sync($validated['category_ids']);

        // Mettre à jour l'image si une nouvelle image est fournie
        if ($request->hasFile('image')) {
            $exercise->clearMediaCollection(Exercise::MEDIA_IMAGE);
            $exercise->addMediaFromRequest('image')
                ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);
        }

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice modifié avec succès.');
    }

    /**
     * Display the specified exercise.
     */
    public function show(Request $request, Exercise $exercise)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut voir cet exercice
        if ($exercise->user_id !== Auth::id() && (!$user || !$user->isAdmin()) && !$exercise->is_shared) {
            // Si c'est une requête AJAX avec le paramètre json=true (mais pas Inertia), retourner du JSON
            $isInertiaRequest = $request->header('X-Inertia') !== null;
            $wantsJson = $request->has('json') || ($request->wantsJson() && !$isInertiaRequest);
            
            if ($wantsJson && !$isInertiaRequest) {
                return response()->json(['error' => 'Vous n\'avez pas les permissions pour voir cet exercice.'], 403);
            }
            return redirect()->route('exercises.index')
                ->with('error', 'Vous n\'avez pas les permissions pour voir cet exercice.');
        }

        $exercise->load(['categories', 'sessions.customer', 'user']);

        $data = [
            'exercise' => [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'description' => $exercise->description,
                'suggested_duration' => $exercise->suggested_duration,
                'image_url' => $exercise->image_url,
                'is_shared' => $exercise->is_shared,
                'created_at' => optional($exercise->created_at)->toDateTimeString(),
                'user_id' => $exercise->user_id,
                'user_name' => $exercise->user?->name,
            ],
            'categories' => $exercise->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'categories_list' => Category::orderBy('name')->get(['id', 'name']),
        ];

        // Si c'est une requête AJAX avec le paramètre json=true (mais pas Inertia), retourner du JSON
        // Inertia envoie le header X-Inertia, donc on vérifie qu'il n'est pas présent
        $isInertiaRequest = $request->header('X-Inertia') !== null;
        $wantsJson = $request->has('json') || ($request->wantsJson() && !$isInertiaRequest);
        
        if ($wantsJson && !$isInertiaRequest) {
            return response()->json($data);
        }

        // Sinon, retourner la page Inertia complète
        $data['sessions'] = $exercise->sessions->map(fn ($session) => [
            'id' => $session->id,
            'name' => $session->name,
            'session_date' => optional($session->session_date)->format('Y-m-d'),
            'customer' => $session->customer ? [
                'id' => $session->customer->id,
                'first_name' => $session->customer->first_name,
                'last_name' => $session->customer->last_name,
            ] : null,
            'pivot' => [
                'repetitions' => $session->pivot->repetitions,
                'rest_time' => $session->pivot->rest_time,
                'duration' => $session->pivot->duration,
                'additional_description' => $session->pivot->additional_description,
                'order' => $session->pivot->order,
            ],
        ]);

        return Inertia::render('exercises/Show', $data);
    }

    /**
     * Get available public exercises for import (excluding user's own exercises and already imported ones).
     */
    public function available(Request $request)
    {
        $userId = Auth::id();
        
        // Récupérer les exercices publics qui ne sont pas déjà possédés par l'utilisateur
        $availableExercises = Exercise::shared()
            ->where('user_id', '!=', $userId)
            ->with(['categories', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Récupérer les exercices que l'utilisateur possède déjà
        $userExercises = Exercise::where('user_id', $userId)->get(['title', 'description']);
        
        // Filtrer les exercices publics pour exclure ceux que l'utilisateur a déjà importés
        // (même titre et description)
        $filteredExercises = $availableExercises->filter(function ($exercise) use ($userExercises) {
            return !$userExercises->contains(function ($userExercise) use ($exercise) {
                return $userExercise->title === $exercise->title 
                    && $userExercise->description === $exercise->description;
            });
        });

        $data = [
            'exercises' => $filteredExercises->map(fn (Exercise $exercise) => [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'description' => $exercise->description,
                'image_url' => $exercise->image_url,
                'category_name' => $exercise->categories->first()?->name ?? 'Sans catégorie',
                'user_name' => $exercise->user?->name,
                'created_at' => optional($exercise->created_at)->toDateTimeString(),
            ])->values(),
        ];

        // Si c'est une requête AJAX (mais pas Inertia), retourner du JSON
        $isInertiaRequest = $request->header('X-Inertia') !== null;
        $wantsJson = $request->has('json') || ($request->wantsJson() && !$isInertiaRequest);
        
        if ($wantsJson && !$isInertiaRequest) {
            return response()->json($data);
        }

        return response()->json($data);
    }

    /**
     * Import a shared exercise (create a copy for the current user).
     */
    public function import(Exercise $exercise)
    {
        $userId = Auth::id();
        
        // Vérifier que l'exercice est public
        if (!$exercise->is_shared) {
            return redirect()->route('exercises.index')
                ->with('error', 'Cet exercice n\'est pas public et ne peut pas être importé.');
        }

        // Vérifier que l'utilisateur ne possède pas déjà cet exercice
        if ($exercise->user_id === $userId) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous possédez déjà cet exercice.');
        }

        // Vérifier qu'on n'a pas déjà importé cet exercice
        $existingExercise = Exercise::where('user_id', $userId)
            ->where('title', $exercise->title)
            ->where('description', $exercise->description)
            ->first();

        if ($existingExercise) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous avez déjà importé cet exercice.');
        }

        // Créer une copie de l'exercice pour l'utilisateur
        $newExercise = Exercise::create([
            'user_id' => $userId,
            'title' => $exercise->title,
            'description' => $exercise->description,
            'suggested_duration' => $exercise->suggested_duration,
            'is_shared' => false, // L'exercice importé est privé par défaut
        ]);

        // Copier les catégories
        $categoryIds = $exercise->categories->pluck('id')->toArray();
        if (!empty($categoryIds)) {
            $newExercise->categories()->attach($categoryIds);
        }

        // Copier l'image si elle existe
        $originalMedia = $exercise->getFirstMedia(Exercise::MEDIA_IMAGE);
        if ($originalMedia) {
            $newExercise->addMediaFromUrl($originalMedia->getUrl())
                ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);
        }

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice importé avec succès.');
    }

    /**
     * Delete an exercise.
     */
    public function destroy(Exercise $exercise)
    {
        $userId = Auth::id();
        
        // Vérifier que l'utilisateur peut supprimer cet exercice
        // Seul le propriétaire peut supprimer (y compris les exercices importés)
        if ($exercise->user_id !== $userId) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer cet exercice.');
        }

        // Supprimer l'exercice (les relations seront supprimées automatiquement via les contraintes de clé étrangère)
        $exercise->delete();

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice supprimé avec succès.');
    }
}

