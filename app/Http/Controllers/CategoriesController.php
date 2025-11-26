<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): Response
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $showPrivate = $request->boolean('private', true);
        $showPublic = $request->boolean('public', true);

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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

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
    public function update(Request $request, Category $category): RedirectResponse
    {
        if (!$this->userCanManage($category)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    /**
     * Delete a private category owned by the authenticated user.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if (!$this->userCanManage($category)) {
            abort(403);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée.');
    }

    /**
     * Determine whether the current user owns the private category.
     */
    private function userCanManage(Category $category): bool
    {
        return $category->type === 'private' && $category->user_id === Auth::id();
    }
}

