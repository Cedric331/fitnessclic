<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exercises\GenerateAiImageRequest;
use App\Http\Requests\Exercises\IndexExerciseRequest;
use App\Http\Requests\Exercises\StoreAiExerciseRequest;
use App\Http\Requests\Exercises\StoreExerciseRequest;
use App\Http\Requests\Exercises\UpdateExerciseRequest;
use App\Http\Requests\Exercises\UploadFilesExerciseRequest;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\UserCredit;
use App\Services\ExerciseImageGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            ->where('is_shared', true)
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
        $user = Auth::user();

        if (! $user->can('create', Exercise::class)) {
            return redirect()->route('exercises.index')
                ->with('error', 'La création d\'exercices est réservée aux abonnés Pro. Passez à Pro pour créer des exercices illimités.');
        }

        $validated = $request->validated();

        $exercise = Exercise::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'suggested_duration' => $validated['suggested_duration'] ?? null,
            'is_shared' => true,
        ]);

        $exercise->categories()->attach($validated['category_ids']);

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
            'is_shared' => true,
        ]);

        $exercise->categories()->sync($validated['category_ids']);

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
        $user = Auth::user();

        if (! $exercise->is_shared) {
            return redirect()->route('exercises.index')
                ->with('error', 'Cet exercice n\'est pas disponible.');
        }

        $userId = Auth::id();

        $exercise->load(['categories', 'user']);

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

        $isInertiaRequest = $request->header('X-Inertia') !== null;
        $wantsJson = $request->has('json') || ($request->wantsJson() && ! $isInertiaRequest);

        if ($wantsJson && ! $isInertiaRequest) {
            return response()->json($data);
        }

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
        $user = Auth::user();

        if (! $user->can('import', Exercise::class)) {
            return redirect()->route('exercises.index')
                ->with('error', 'L\'import d\'exercices est réservé aux abonnés Pro. Passez à Pro pour importer des exercices illimités.');
        }

        $userId = Auth::id();
        $createdCount = 0;
        $errors = [];
        $files = $request->file('files');
        $categoryIds = $validated['category_ids'];

        foreach ($files as $file) {
            try {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $exercise = Exercise::create([
                    'user_id' => $userId,
                    'title' => $filename,
                    'description' => null,
                    'suggested_duration' => null,
                    'is_shared' => true,
                ]);

                $exercise->categories()->attach($categoryIds);

                $optimizedFile = $this->optimizeImage($file);
                $exercise->addMedia($optimizedFile)
                    ->usingName($filename)
                    ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = 'Erreur lors de la création de l\'exercice pour '.$file->getClientOriginalName().': '.$e->getMessage();
            }
        }

        if ($createdCount > 0) {
            $message = $createdCount.' exercice(s) créé(s) avec succès.';
            if (! empty($errors)) {
                $message .= ' '.count($errors).' erreur(s) rencontrée(s) : '.implode(', ', $errors);
            }

            return redirect()->route('exercises.index')
                ->with('success', $message);
        }

        $errorMessage = 'Aucun exercice n\'a pu être créé.';
        if (! empty($errors)) {
            $errorMessage .= ' '.implode(', ', $errors);
        }

        return redirect()->route('exercises.index')
            ->with('error', $errorMessage);
    }

    /**
     * Get the current AI credits balance for the authenticated user.
     */
    public function getAiCredits(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $isAdmin = $user->isAdmin();

        return response()->json([
            'credits' => $isAdmin ? -1 : $user->getAiCreditsBalance(), // -1 indicates unlimited
            'can_generate' => $isAdmin || $user->canGenerateAiImages(),
            'is_pro' => $user->isPro(),
            'is_admin' => $isAdmin,
        ]);
    }

    /**
     * Generate an exercise image using AI.
     */
    public function generateImage(
        GenerateAiImageRequest $request,
        ExerciseImageGeneratorService $imageGenerator
    ): JsonResponse {
        $user = Auth::user();
        $validated = $request->validated();

        // Check if user has an active subscription
        if (! $user->isPro()) {
            return response()->json([
                'error' => 'La génération d\'images IA est réservée aux abonnés Pro.',
                'code' => 'subscription_required',
            ], 403);
        }

        // Admins have unlimited credits
        $isAdmin = $user->isAdmin();

        // Check if user has enough credits (skip for admins)
        if (! $isAdmin && ! $user->hasAiCredits()) {
            return response()->json([
                'error' => 'Vous n\'avez plus de crédits IA disponibles. Vos crédits seront rechargés lors du prochain renouvellement de votre abonnement.',
                'code' => 'no_credits',
                'credits' => 0,
            ], 403);
        }

        $metadata = [
            'exercise_name' => $validated['exercise_name'],
            'description' => $validated['description'] ?? null,
        ];

        try {
            $result = $imageGenerator->generate(
                $validated['exercise_name'],
                $validated['description'] ?? null
            );

            // Deduct credit after successful generation (skip for admins - unlimited credits)
            if (! $isAdmin) {
                $user->deductAiCredits(1, UserCredit::REASON_IMAGE_GENERATION, $metadata);
            }

            return response()->json([
                'success' => true,
                'image_base64' => $result['base64'],
                'credits' => $isAdmin ? -1 : $user->getAiCreditsBalance(), // -1 indicates unlimited
                'is_admin' => $isAdmin,
            ]);
        } catch (\Exception $e) {
            // Log the failed attempt without deducting credits
            $user->logFailedAiGeneration($e->getMessage(), $metadata);
dd($e);
            Log::error('AI image generation failed', [
                'user_id' => $user->id,
                'exercise_name' => $validated['exercise_name'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la génération de l\'image. Vous pouvez réessayer, aucun crédit n\'a été déduit.',
                'code' => 'generation_failed',
                'credits' => $isAdmin ? -1 : $user->getAiCreditsBalance(),
            ], 500);
        }
    }

    /**
     * Store an exercise created with AI-generated image.
     */
    public function storeFromAi(StoreAiExerciseRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->can('create', Exercise::class)) {
            return response()->json([
                'error' => 'La création d\'exercices est réservée aux abonnés Pro.',
            ], 403);
        }

        $validated = $request->validated();

        // Decode the base64 image and save to temp file
        $imageData = base64_decode($validated['image_base64']);
        $tempPath = tempnam(sys_get_temp_dir(), 'ai_exercise_');
        file_put_contents($tempPath, $imageData);

        try {
            $exercise = Exercise::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'suggested_duration' => $validated['suggested_duration'] ?? null,
                'is_shared' => true,
            ]);

            $exercise->categories()->attach($validated['category_ids']);

            // Create an UploadedFile from the temp path for optimization
            $uploadedFile = new UploadedFile(
                $tempPath,
                $validated['title'] . '.png',
                'image/png',
                null,
                true
            );

            $optimizedFile = $this->optimizeImage($uploadedFile);

            $exercise->addMedia($optimizedFile->getRealPath())
                ->usingName($validated['title'])
                ->toMediaCollection(Exercise::MEDIA_IMAGE, Exercise::MEDIA_DISK);

            // Clean up temp files
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Exercice créé avec succès.',
                'exercise' => [
                    'id' => $exercise->id,
                    'name' => $exercise->title,
                    'image_url' => $exercise->image_url,
                ],
            ]);
        } catch (\Exception $e) {
            // Clean up temp file on error
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            Log::error('Failed to create exercise from AI image', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la création de l\'exercice.',
            ], 500);
        }
    }

    /**
     * Delete an exercise.
     */
    public function destroy(Exercise $exercise)
    {
        $user = Auth::user();

        if ($exercise->user_id !== Auth::id() && (! $user || ! $user->isAdmin())) {
            return redirect()->route('exercises.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer cet exercice.');
        }

        $exercise->delete();

        return redirect()->route('exercises.index')
            ->with('success', 'Exercice supprimé avec succès.');
    }

    /**
     * Optimize an uploaded image by resizing and compressing it
     */
    private function optimizeImage(UploadedFile $file): UploadedFile
    {
        $maxWidth = 1920;
        $maxHeight = 1080;
        $quality = 85;

        $imageInfo = getimagesize($file->getRealPath());
        if (! $imageInfo) {
            return $file;
        }

        [$originalWidth, $originalHeight, $imageType] = $imageInfo;

        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            if ($imageType === IMAGETYPE_JPEG) {
                return $this->compressJpeg($file, $quality);
            }

            return $file;
        }

        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_GIF => imagecreatefromgif($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => null,
        };

        if (! $sourceImage) {
            return $file;
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'optimized_');
        $success = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($newImage, $tempPath, $quality),
            IMAGETYPE_PNG => imagepng($newImage, $tempPath, 9),
            IMAGETYPE_GIF => imagegif($newImage, $tempPath),
            IMAGETYPE_WEBP => imagewebp($newImage, $tempPath, $quality),
            default => false,
        };

        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (! $success) {
            unlink($tempPath);

            return $file;
        }

        $optimizedFile = new UploadedFile(
            $tempPath,
            $file->getClientOriginalName(),
            $file->getMimeType(),
            null,
            true
        );

        return $optimizedFile;
    }

    /**
     * Compress a JPEG image without resizing
     */
    private function compressJpeg(UploadedFile $file, int $quality = 85): UploadedFile
    {
        $imageInfo = getimagesize($file->getRealPath());
        if (! $imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
            return $file;
        }

        $sourceImage = imagecreatefromjpeg($file->getRealPath());
        if (! $sourceImage) {
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
