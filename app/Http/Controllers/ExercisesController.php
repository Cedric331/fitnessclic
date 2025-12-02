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
        $viewMode = $request->input('view', 'grid-6');

        $exercisesQuery = Exercise::query()
            ->where('is_shared', true) // Seulement les exercices visibles (non cachés par l'admin)
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

        return Inertia::render('exercises/Index', [
            'exercises' => [
                'data' => $exercises->map(fn (Exercise $exercise) => [
                    'id' => $exercise->id,
                    'name' => $exercise->title,
                    'image_url' => $exercise->image_url,
                    'user_id' => $exercise->user_id,
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
                'view' => in_array($viewMode, ['grid-2', 'grid-4', 'grid-6', 'grid-8'], true) ? $viewMode : 'grid-6',
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
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
        ]);

        $exercise = Exercise::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => true, // All exercises are public by default
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
        ]);

        $exercise->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => true, // All exercises are public
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
        
        // Vérifier que l'exercice est visible (non caché par l'admin)
        if (!$exercise->is_shared) {
            return redirect()->route('exercises.index')
                ->with('error', 'Cet exercice n\'est pas disponible.');
        }

        $userId = Auth::id();
        
        // Charger les catégories et l'utilisateur créateur
        $exercise->load(['categories', 'user']);

        // Charger uniquement les séances de l'utilisateur connecté
        $userSessions = $exercise->sessions()
            ->where('user_id', $userId)
            ->with('customer')
            ->get();

        $data = [
            'exercise' => [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'description' => $exercise->description,
                'suggested_duration' => $exercise->suggested_duration,
                'image_url' => $exercise->image_url,
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

        // Sinon, retourner la page Inertia complète avec seulement les séances de l'utilisateur
        $data['sessions'] = $userSessions->map(fn ($session) => [
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
     * Upload multiple files to create exercises.
     * Each file will create an exercise with the filename (without extension) as the title.
     */
    public function uploadFiles(Request $request)
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'image', 'max:5120'], // 5MB max per file
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'exists:categories,id'],
        ]);

        $userId = Auth::id();
        $createdCount = 0;
        $errors = [];
        $files = $request->file('files');
        $categoryIds = $validated['category_ids'];

        foreach ($files as $file) {
            try {
                // Get filename without extension
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                
                // Create exercise with filename as title
                $exercise = Exercise::create([
                    'user_id' => $userId,
                    'title' => $filename,
                    'description' => null,
                    'suggested_duration' => null,
                    'is_shared' => true, // All exercises are public by default
                ]);

                // Attach categories
                $exercise->categories()->attach($categoryIds);

                // Add the image
                $exercise->addMedia($file)
                    ->usingName($filename)
                    ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = 'Erreur lors de la création de l\'exercice pour ' . $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($createdCount > 0) {
            $message = $createdCount . ' exercice(s) créé(s) avec succès.';
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' erreur(s) rencontrée(s) : ' . implode(', ', $errors);
            }
            return redirect()->route('exercises.index')
                ->with('success', $message);
        }

        $errorMessage = 'Aucun exercice n\'a pu être créé.';
        if (!empty($errors)) {
            $errorMessage .= ' ' . implode(', ', $errors);
        }
        
        return redirect()->route('exercises.index')
            ->with('error', $errorMessage);
    }

    /**
     * Delete an exercise.
     */
    public function destroy(Exercise $exercise)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut supprimer cet exercice
        // Seul le créateur ou un admin peut supprimer
        if ($exercise->user_id !== Auth::id() && (!$user || !$user->isAdmin())) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer cet exercice.');
        }

        // Supprimer l'exercice (les relations seront supprimées automatiquement via les contraintes de clé étrangère)
        $exercise->delete();

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice supprimé avec succès.');
    }
}

