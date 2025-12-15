<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Cashier\Subscription;

test('unauthenticated user cannot access categories index', function () {
    $response = $this->get(route('categories.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view categories index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('categories.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('categories/Index')
        ->has('privateCategories')
        ->has('publicCategories')
        ->has('filters')
    );
});

test('categories index filters by search term', function () {
    $user = User::factory()->create();
    Category::factory()->private()->for($user)->create(['name' => 'Cardio']);
    Category::factory()->private()->for($user)->create(['name' => 'Musculation']);

    $response = $this->actingAs($user)->get(route('categories.index', ['search' => 'Cardio']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'Cardio')
    );
});

test('categories index can filter private and public categories', function () {
    $user = User::factory()->create();
    Category::factory()->private()->for($user)->create();
    Category::factory()->public()->create();

    $response = $this->actingAs($user)->get(route('categories.index', [
        'private' => true,
        'public' => false,
    ]));

    $response->assertStatus(200);
    // Laravel convertit les valeurs booléennes en '1'/'0' (strings) lors de la validation
    // Le contrôleur retourne ces valeurs telles quelles
    $response->assertInertia(fn ($page) => $page
        ->where('filters.show_private', '1')
        ->where('filters.show_public', '0')
    );
});

test('free user cannot create category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Nouvelle Catégorie',
    ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error', 'La création de catégories est réservée aux abonnés Pro. Passez à Pro pour créer des catégories illimitées.');
    $this->assertDatabaseMissing('categories', ['name' => 'Nouvelle Catégorie']);
});

test('pro user can create private category', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);

    $response = $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Nouvelle Catégorie',
    ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Catégorie créée avec succès.');
    $this->assertDatabaseHas('categories', [
        'name' => 'Nouvelle Catégorie',
        'type' => 'private',
        'user_id' => $user->id,
    ]);
});

test('admin user can create public category', function () {
    $admin = User::factory()->admin()->create();
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'stripe_status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Catégorie Publique',
    ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Catégorie créée avec succès.');
    $this->assertDatabaseHas('categories', [
        'name' => 'Catégorie Publique',
        'type' => 'public',
        'user_id' => $admin->id,
    ]);
});

test('free user cannot update category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'type' => 'private',
        'user_id' => $user->id,
        'name' => 'Ma Catégorie',
    ]);

    $response = $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => 'Catégorie Modifiée',
    ]);

    // UpdateCategoryRequest.authorize() retourne true car c'est sa catégorie privée
    // Le contrôleur vérifie can('update') qui retourne false car pas Pro
    // Donc le contrôleur redirige avec erreur
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error', 'La modification de catégories est réservée aux abonnés Pro.');
    $this->assertDatabaseMissing('categories', ['name' => 'Catégorie Modifiée']);
});

test('pro user can update own category', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->create([
        'type' => 'private',
        'user_id' => $user->id,
        'name' => 'Ancien Nom',
    ]);

    $response = $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => 'Nouveau Nom',
    ]);

    // UpdateCategoryRequest.authorize() retourne true car c'est sa catégorie privée
    // Le contrôleur vérifie can('update') qui retourne true car Pro
    // Donc la mise à jour fonctionne
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Catégorie mise à jour.');
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Nouveau Nom',
    ]);
});

test('user cannot update another user category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->private()->for($otherUser)->create();

    $response = $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => 'Tentative Modification',
    ]);

    $response->assertForbidden();
});

test('free user cannot delete category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->private()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error', 'La suppression de catégories est réservée aux abonnés Pro.');
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('pro user can delete own private category', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->create([
        'type' => 'private',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Catégorie supprimée.');
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('user cannot delete public category', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->public()->create();

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('user cannot delete another user private category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);
    $category = Category::factory()->private()->for($otherUser)->create();

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
