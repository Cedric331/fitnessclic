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

        $perPage = 8;
        $exercises = $exercisesQuery->paginate($perPage);

        return Inertia::render('exercises/Index', [
            'exercises' => [
                'data' => $exercises->map(fn (Exercise $exercise) => [
                    'id' => $exercise->id,
                    'name' => $exercise->title,
                    'image_url' => $exercise->image_url,
                    'category_name' => $exercise->categories->first()?->name ?? 'Sans catégorie',
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
                'view' => in_array($viewMode, ['grid-1', 'grid-2', 'grid-4', 'list'], true) ? $viewMode : 'grid-4',
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

