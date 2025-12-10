<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\IndexCategoryRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $privateCategories = collect();
        if ($showPrivate && Auth::id()) {
            $privateCategories = Category::private()
                ->where('user_id', Auth::id())
                ->when($searchTerm !== '', fn ($query) => $query->where('name', 'like', "%{$searchTerm}%"))
                ->orderBy('name')
                ->get();
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

        if ($user->isFree()) {
            return redirect()->route('categories.index')
                ->with('error', 'La création de catégories est réservée aux abonnés Pro. Passez à Pro pour créer des catégories illimitées.');
        }

        Category::create([
            'name' => $validated['name'],
            'type' => 'private',
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

        if ($user->isFree()) {
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

        if ($user->isFree()) {
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

