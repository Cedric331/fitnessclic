<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\IndexCategoryRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    /**
     * Display the categories dashboard.
     */
    public function index(IndexCategoryRequest $request): Response
    {
        $validated = $request->validated();
        $searchTerm = trim((string) ($validated['search'] ?? ''));
        $showPrivate = $validated['private'] ?? true;
        $showPublic = $validated['public'] ?? true;
        $user = Auth::user();
        $ownership = $validated['ownership'] ?? 'all';

        $privateCategories = collect();
        if ($showPrivate && $user) {
            $teamMemberIds = $user->teamMemberIds();
            $privateCategories = Category::private()
                ->with('user')
                ->whereIn('user_id', $teamMemberIds)
                ->when($searchTerm !== '', fn ($query) => $query->where('name', 'like', "%{$searchTerm}%"))
                ->orderBy('name')
                ->get()
                ->map(function (Category $category) use ($user) {
                    $category->is_owner = $category->user_id === $user->id;
                    $category->coach_name = $category->user?->name;

                    return $category;
                });
        }

        if ($user && ! $user->team_id) {
            $ownership = 'mine';
        }

        if ($ownership !== 'all' && $privateCategories->isNotEmpty()) {
            $privateCategories = $privateCategories->filter(function (Category $category) use ($user, $ownership) {
                if ($ownership === 'mine') {
                    return $category->user_id === $user->id;
                }

                return $category->user_id !== $user->id;
            })->values();
        }

        $publicCategories = collect();
        if ($showPublic) {
            $publicCategories = Category::public()
                ->when($searchTerm !== '', fn ($query) => $query->where('name', 'like', "%{$searchTerm}%"))
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('categories/Index', [
            'privateCategories' => $privateCategories,
            'publicCategories' => $publicCategories,
            'filters' => [
                'search' => $searchTerm ?: null,
                'show_private' => $showPrivate,
                'show_public' => $showPublic,
                'ownership' => $ownership,
            ],
        ]);
    }

    /**
     * Store a new private category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        if (! $user->can('create', Category::class)) {
            return redirect()->route('categories.index')
                ->with('error', 'La création de catégories est réservée aux abonnés Pro. Passez à Pro pour créer des catégories illimitées.');
        }

        $isAdmin = $user->isAdmin();
        if ($isAdmin) {
            $type = 'public';
        } else {
            $type = 'private';
        }

        Category::create([
            'name' => $validated['name'],
            'type' => $type,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Update a private category owned by the authenticated user.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->can('update', $category)) {
            return redirect()->route('categories.index')
                ->with('error', 'La modification de catégories est réservée aux abonnés Pro.');
        }

        $validated = $request->validated();

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    /**
     * Delete a private category owned by the authenticated user.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->can('delete', $category)) {
            return redirect()->route('categories.index')
                ->with('error', 'La suppression de catégories est réservée aux abonnés Pro.');
        }

        if ($category->type !== 'private' || $category->user_id !== Auth::id()) {
            abort(403);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}
