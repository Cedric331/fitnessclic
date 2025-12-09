<?php

use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\PublicSessionController;
use App\Http\Controllers\SessionsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Models\Session;
use App\Models\Customer;
use App\Mail\SessionEmail;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// dashboard redirect to create a session
Route::get('dashboard', function () {
    return redirect()->route('sessions.create');
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
    Route::get('/exercises/{exercise}', [ExercisesController::class, 'show'])->name('exercises.show');
    Route::post('/exercises', [ExercisesController::class, 'store'])->name('exercises.store');
    Route::post('/exercises/upload-files', [ExercisesController::class, 'uploadFiles'])->name('exercises.upload-files');
    Route::put('/exercises/{exercise}', [ExercisesController::class, 'update'])->name('exercises.update');
    Route::delete('/exercises/{exercise}', [ExercisesController::class, 'destroy'])->name('exercises.destroy');

    // Categories routes
    Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

    // Sessions routes
    Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [SessionsController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionsController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}', [SessionsController::class, 'show'])->name('sessions.show');
    Route::get('/sessions/{session}/edit', [SessionsController::class, 'edit'])->name('sessions.edit');
    Route::put('/sessions/{session}', [SessionsController::class, 'update'])->name('sessions.update');
    Route::delete('/sessions/{session}', [SessionsController::class, 'destroy'])->name('sessions.destroy');
    Route::get('/sessions/{session}/pdf', [SessionsController::class, 'pdf'])->name('sessions.pdf');
    Route::post('/sessions/pdf-preview', [SessionsController::class, 'pdfPreview'])->name('sessions.pdf-preview');
    Route::post('/sessions/{session}/send-email', [SessionsController::class, 'sendEmail'])->name('sessions.send-email');
});

// Route publique pour consulter une séance via token
Route::get('/session/{shareToken}', [PublicSessionController::class, 'show'])
    ->name('public.session.show');

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

// test email
Route::get('test-email', function () {
    $session = Session::first();
    $customer = Customer::first();

    return (new SessionEmail($session, $customer))->render();
});

require __DIR__.'/settings.php';
