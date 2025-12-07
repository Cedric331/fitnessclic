<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exercises\IndexExerciseRequest;
use App\Http\Requests\Exercises\StoreExerciseRequest;
use App\Http\Requests\Exercises\UpdateExerciseRequest;
use App\Http\Requests\Exercises\UploadFilesExerciseRequest;
use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExercisesController extends Controller
{
    /**
     * Display the exercises library.
     */
    public function index(IndexExerciseRequest $request): Response
    {
        $validated = $request->validated();
        $searchTerm = trim((string) ($validated['search'] ?? ''));
        $categoryId = $validated['category_id'] ?? null;
        $sortOrder = $validated['sort'] ?? 'newest';
        $viewMode = $validated['view'] ?? 'grid-6';

        $exercisesQuery = Exercise::query()
            ->where('is_shared', true) // Seulement les exercices visibles (non cachés par l'admin)
            ->with('categories')
            ->when($searchTerm !== '', fn ($query) => $query->where('title', 'like', "%{$searchTerm}%"))
            ->when($categoryId, fn ($query, $value) => $query->whereHas('categories', fn ($query) => $query->where('categories.id', $value)));

        if ($sortOrder === 'oldest') {
            $exercisesQuery->orderBy('created_at', 'asc');
        } elseif ($sortOrder === 'alphabetical') {
            $exercisesQuery->orderBy('title', 'asc');
        } elseif ($sortOrder === 'alphabetical-desc') {
            $exercisesQuery->orderBy('title', 'desc');
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
                'sort' => in_array($sortOrder, ['newest', 'oldest', 'alphabetical', 'alphabetical-desc'], true) ? $sortOrder : 'newest',
                'view' => in_array($viewMode, ['grid-2', 'grid-4', 'grid-6', 'grid-8'], true) ? $viewMode : 'grid-6',
            ],
            'categories' => Category::forUser(Auth::id())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created exercise.
     */
    public function store(StoreExerciseRequest $request)
    {
        $validated = $request->validated();

        $exercise = Exercise::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => true, // All exercises are public by default
        ]);

        // Attacher les catégories
        $exercise->categories()->attach($validated['category_ids']);

        // Ajouter l'image avec optimisation
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $optimizedFile = $this->optimizeImage($file);
            
            $exercise->addMedia($optimizedFile)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);
        }

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice créé avec succès.');
    }

    /**
     * Update the specified exercise.
     */
    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $validated = $request->validated();

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
            $file = $request->file('image');
            $optimizedFile = $this->optimizeImage($file);
            
            $exercise->addMedia($optimizedFile)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
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
            ->with('customers')
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
            'categories_list' => Category::forUser(Auth::id())->orderBy('name')->get(['id', 'name']),
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
            'customer' => $session->customers->first() ? [
                'id' => $session->customers->first()->id,
                'first_name' => $session->customers->first()->first_name,
                'last_name' => $session->customers->first()->last_name,
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
    public function uploadFiles(UploadFilesExerciseRequest $request)
    {
        $validated = $request->validated();

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

                // Add the image with optimization
                $optimizedFile = $this->optimizeImage($file);
                $exercise->addMedia($optimizedFile)
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

    /**
     * Optimize an uploaded image by resizing and compressing it
     * 
     * @param UploadedFile $file
     * @return UploadedFile
     */
    private function optimizeImage(UploadedFile $file): UploadedFile
    {
        $maxWidth = 1920;
        $maxHeight = 1080;
        $quality = 85;

        // Get image info
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            return $file; // Return original if not an image
        }

        [$originalWidth, $originalHeight, $imageType] = $imageInfo;

        // Calculate new dimensions while maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        // If image is already smaller than max dimensions, return original
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            // Still compress if it's a JPEG
            if ($imageType === IMAGETYPE_JPEG) {
                return $this->compressJpeg($file, $quality);
            }
            return $file;
        }

        // Create image resource based on type
        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_GIF => imagecreatefromgif($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => null,
        };

        if (!$sourceImage) {
            return $file; // Return original if we can't process it
        }

        // Create new image with new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Save optimized image to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'optimized_');
        $success = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($newImage, $tempPath, $quality),
            IMAGETYPE_PNG => imagepng($newImage, $tempPath, 9), // PNG compression level 0-9
            IMAGETYPE_GIF => imagegif($newImage, $tempPath),
            IMAGETYPE_WEBP => imagewebp($newImage, $tempPath, $quality),
            default => false,
        };

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (!$success) {
            unlink($tempPath);
            return $file; // Return original if save failed
        }

        // Create new UploadedFile from optimized image
        $optimizedFile = new UploadedFile(
            $tempPath,
            $file->getClientOriginalName(),
            $file->getMimeType(),
            null,
            true // Mark as test file so it gets cleaned up
        );

        return $optimizedFile;
    }

    /**
     * Compress a JPEG image without resizing
     * 
     * @param UploadedFile $file
     * @param int $quality
     * @return UploadedFile
     */
    private function compressJpeg(UploadedFile $file, int $quality = 85): UploadedFile
    {
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
            return $file;
        }

        $sourceImage = imagecreatefromjpeg($file->getRealPath());
        if (!$sourceImage) {
            return $file;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'compressed_');
        imagejpeg($sourceImage, $tempPath, $quality);
        imagedestroy($sourceImage);

        return new UploadedFile(
            $tempPath,
            $file->getClientOriginalName(),
            $file->getMimeType(),
            null,
            true
        );
    }
}

