<?php

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Subscription;

beforeEach(function () {
    Storage::fake('public');
});

test('unauthenticated user cannot access exercises index', function () {
    $response = $this->get(route('exercises.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view exercises index', function () {
    $user = User::factory()->create();
    Exercise::factory()->count(5)->shared()->create();

    $response = $this->actingAs($user)->get(route('exercises.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('exercises/Index')
        ->has('exercises.data', 5)
        ->has('categories')
    );
});

test('exercises index filters by search term', function () {
    $user = User::factory()->create();
    Exercise::factory()->shared()->create(['title' => 'Squat']);
    Exercise::factory()->shared()->create(['title' => 'Push-up']);

    $response = $this->actingAs($user)->get(route('exercises.index', ['search' => 'Squat']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'Squat')
    );
});

test('exercises index filters by category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->public()->create();
    $exercise = Exercise::factory()->shared()->create();
    $exercise->categories()->attach($category);

    $response = $this->actingAs($user)->get(route('exercises.index', ['category_id' => $category->id]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.category_id', $category->id)
    );
});

test('exercises index can sort by different orders', function () {
    $user = User::factory()->create();
    Exercise::factory()->shared()->create(['title' => 'B Exercise', 'created_at' => now()->subDays(2)]);
    Exercise::factory()->shared()->create(['title' => 'A Exercise', 'created_at' => now()->subDays(1)]);

    $response = $this->actingAs($user)->get(route('exercises.index', ['sort' => 'alphabetical']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort', 'alphabetical')
    );
});

test('free user cannot create exercise', function () {
    $user = User::factory()->create();
    $category = Category::factory()->public()->create();
    $image = UploadedFile::fake()->image('exercise.jpg');

    $response = $this->actingAs($user)->post(route('exercises.store'), [
        'title' => 'Nouvel Exercice',
        'description' => 'Description',
        'category_ids' => [$category->id],
        'image' => $image,
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('error', 'La création d\'exercices est réservée aux abonnés Pro. Passez à Pro pour créer des exercices illimités.');
    $this->assertDatabaseMissing('exercises', ['title' => 'Nouvel Exercice']);
});

test('pro user can create exercise', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->public()->create();
    $image = UploadedFile::fake()->image('exercise.jpg');

    $response = $this->actingAs($user)->post(route('exercises.store'), [
        'title' => 'Nouvel Exercice',
        'description' => 'Description',
        'category_ids' => [$category->id],
        'image' => $image,
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice créé avec succès.');
    $this->assertDatabaseHas('exercises', [
        'title' => 'Nouvel Exercice',
        'user_id' => $user->id,
        'is_shared' => true,
    ]);
    $exercise = Exercise::where('title', 'Nouvel Exercice')->first();
    expect($exercise->categories)->toHaveCount(1);
});

test('pro user can create exercise with image', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->public()->create();
    $image = UploadedFile::fake()->image('exercise.jpg');

    $response = $this->actingAs($user)->post(route('exercises.store'), [
        'title' => 'Exercice avec Image',
        'category_ids' => [$category->id],
        'image' => $image,
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice créé avec succès.');
    $exercise = Exercise::where('title', 'Exercice avec Image')->first();
    expect($exercise->getMedia('exercise_image'))->not->toBeEmpty();
});

test('authenticated user can view exercise', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->shared()->create();

    $response = $this->actingAs($user)->get(route('exercises.show', $exercise));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('exercises/Show')
        ->has('exercise')
        ->has('categories')
    );
});

test('user cannot view non-shared exercise', function () {
    $user = User::factory()->create();
    // Créer un exercice non partagé qui n'appartient pas à l'utilisateur
    // Note: Le modèle Exercise force is_shared à true lors de la création via booted()
    // Il faut donc le modifier après la création
    $otherUser = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'user_id' => $otherUser->id,
    ]);
    
    // Forcer is_shared à false après la création (car booted() le force à true)
    $exercise->is_shared = false;
    $exercise->save();

    $response = $this->actingAs($user)->get(route('exercises.show', $exercise));

    // Le contrôleur devrait rediriger si l'exercice n'est pas partagé
    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('error', 'Cet exercice n\'est pas disponible.');
});

test('exercise show returns json when requested', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->shared()->create();

    $response = $this->actingAs($user)->get(route('exercises.show', $exercise) . '?json=1');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'exercise',
        'categories',
    ]);
});

test('pro user can update own exercise', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $exercise = Exercise::factory()->shared()->for($user)->create(['title' => 'Ancien Titre']);
    $category = Category::factory()->public()->create();

    $response = $this->actingAs($user)->put(route('exercises.update', $exercise), [
        'title' => 'Nouveau Titre',
        'description' => 'Nouvelle Description',
        'category_ids' => [$category->id],
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice modifié avec succès.');
    $this->assertDatabaseHas('exercises', [
        'id' => $exercise->id,
        'title' => 'Nouveau Titre',
    ]);
    $exercise->refresh();
    expect($exercise->categories->pluck('id')->toArray())->toContain($category->id);
});

test('admin can update any exercise', function () {
    $admin = User::factory()->admin()->create();
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'stripe_status' => 'active',
    ]);
    $otherUser = User::factory()->create();
    $exercise = Exercise::factory()->shared()->for($otherUser)->create();
    $category = Category::factory()->public()->create();

    $response = $this->actingAs($admin)->put(route('exercises.update', $exercise), [
        'title' => 'Modifié par Admin',
        'category_ids' => [$category->id],
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice modifié avec succès.');
    $this->assertDatabaseHas('exercises', [
        'id' => $exercise->id,
        'title' => 'Modifié par Admin',
    ]);
});

test('free user cannot upload exercise files', function () {
    $user = User::factory()->create();
    $category = Category::factory()->public()->create();
    $files = [
        UploadedFile::fake()->image('exercise1.jpg'),
        UploadedFile::fake()->image('exercise2.jpg'),
    ];

    $response = $this->actingAs($user)->post(route('exercises.upload-files'), [
        'files' => $files,
        'category_ids' => [$category->id],
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('error', 'L\'import d\'exercices est réservé aux abonnés Pro. Passez à Pro pour importer des exercices illimités.');
});

test('pro user can upload exercise files', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->public()->create();
    $files = [
        UploadedFile::fake()->image('exercise1.jpg'),
        UploadedFile::fake()->image('exercise2.jpg'),
    ];

    $response = $this->actingAs($user)->post(route('exercises.upload-files'), [
        'files' => $files,
        'category_ids' => [$category->id],
    ]);

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success');
    expect(Exercise::where('user_id', $user->id)->count())->toBe(2);
});

test('user can delete own exercise', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $exercise = Exercise::factory()->shared()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('exercises.destroy', $exercise));

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice supprimé avec succès.');
    $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
});

test('admin can delete any exercise', function () {
    $admin = User::factory()->admin()->create();
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'stripe_status' => 'active',
    ]);
    $otherUser = User::factory()->create();
    $exercise = Exercise::factory()->shared()->for($otherUser)->create();

    $response = $this->actingAs($admin)->delete(route('exercises.destroy', $exercise));

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('success', 'Exercice supprimé avec succès.');
    $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
});

test('user cannot delete another user exercise', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $otherUser = User::factory()->create();
    $exercise = Exercise::factory()->shared()->for($otherUser)->create();

    $response = $this->actingAs($user)->delete(route('exercises.destroy', $exercise));

    $response->assertRedirect(route('exercises.index'));
    $response->assertSessionHas('error', 'Vous n\'avez pas les permissions pour supprimer cet exercice.');
    $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
});

