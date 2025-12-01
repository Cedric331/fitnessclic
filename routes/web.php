<?php

use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ExercisesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('generate', function (){
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    echo 'ok';
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Routes clients
Route::middleware(['auth', 'verified'])->group(function () {
    // Customers routes
    Route::get('/customers', [\App\Http\Controllers\CustomersController::class, 'index'])->name('client.customers.index');
    Route::get('/customers/{customer}', [\App\Http\Controllers\CustomersController::class, 'show'])->name('client.customers.show');
    Route::post('/customers', [\App\Http\Controllers\CustomersController::class, 'store'])->name('client.customers.store');
    Route::put('/customers/{customer}', [\App\Http\Controllers\CustomersController::class, 'update'])->name('client.customers.update');
    Route::delete('/customers/{customer}', [\App\Http\Controllers\CustomersController::class, 'destroy'])->name('client.customers.destroy');

    // Exercises routes
    Route::get('/exercises', [ExercisesController::class, 'index'])->name('exercises.index');
    Route::get('/exercises/available', [ExercisesController::class, 'available'])->name('exercises.available');
    Route::get('/exercises/{exercise}', [ExercisesController::class, 'show'])->name('exercises.show');
    Route::post('/exercises', [ExercisesController::class, 'store'])->name('exercises.store');
    Route::post('/exercises/{exercise}/import', [ExercisesController::class, 'import'])->name('exercises.import');
    Route::put('/exercises/{exercise}', [ExercisesController::class, 'update'])->name('exercises.update');
    Route::delete('/exercises/{exercise}', [ExercisesController::class, 'destroy'])->name('exercises.destroy');

    // Categories routes
    Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
});

// Pages légales
Route::get('mentions-legales', function () {
    return Inertia::render('legal/MentionsLegales');
})->name('legal.mentions');

Route::get('politique-confidentialite', function () {
    return Inertia::render('legal/PolitiqueConfidentialite');
})->name('legal.privacy');

Route::get('conditions-utilisation', function () {
    return Inertia::render('legal/ConditionsUtilisation');
})->name('legal.terms');

Route::get('politique-cookies', function () {
    return Inertia::render('legal/PolitiqueCookies');
})->name('legal.cookies');

require __DIR__.'/settings.php';
