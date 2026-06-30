<?php

use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\ArtisanCommandController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClientSpaceController;
use App\Http\Controllers\CoachDirectoryController;
use App\Http\Controllers\CoachProfileController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\PopinsController;
use App\Http\Controllers\PublicSessionController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationsController;
use App\Models\Session;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Popins publiques (prospects)
Route::get('/popins/active', [PopinsController::class, 'active'])->name('popins.active');
Route::post('/popins/{popin}/prospects', [PopinsController::class, 'storeProspect'])
    ->name('popins.prospects.store');

// Blog public
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Annuaire public des coachs
Route::get('/coachs', [CoachDirectoryController::class, 'index'])->name('coachs.index');
Route::get('/coachs/{slug}', [CoachDirectoryController::class, 'show'])->name('coachs.show');

// dashboard redirect: clients go to their space, coaches to session creation
Route::get('dashboard', function () {
    return redirect()->route(
        auth()->user()?->isClientAccount() ? 'client.space.index' : 'sessions.create'
    );
})->middleware(['auth', 'verified'])->name('dashboard');

// Routes clients
Route::middleware(['auth', 'verified'])->group(function () {
    // Espace client (compte client)
    Route::get('/espace-client', [ClientSpaceController::class, 'index'])->name('client.space.index');

    // Messagerie (coach ↔ client)
    Route::get('/messages', [ConversationsController::class, 'index'])->name('messages.index');
    Route::get('/messages/unread-count', [ConversationsController::class, 'unreadCount'])->name('messages.unread-count');
    Route::get('/messages/coaches/search', [ConversationsController::class, 'searchCoaches'])->name('messages.search-coaches');
    Route::get('/messages/{conversation}', [ConversationsController::class, 'show'])->name('messages.show');

    // Écriture (anti-spam) : throttle par utilisateur authentifié.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/messages/start', [ConversationsController::class, 'start'])->name('messages.start');
        Route::post('/messages/with/{user}', [ConversationsController::class, 'startWith'])->name('messages.start-with');
        Route::post('/messages/{conversation}/reply', [ConversationsController::class, 'reply'])->name('messages.reply');
    });

    // Annonces (visibles par tous les utilisateurs authentifiés)
    Route::get('/announcements/current', [AnnouncementsController::class, 'current'])->name('announcements.current');
    Route::post('/announcements/{announcement}/seen', [AnnouncementsController::class, 'markAsSeen'])->name('announcements.seen');

    // ───────── Routes réservées aux coachs (et admins) ─────────
    Route::middleware('coach')->group(function () {

        // Customers routes
        Route::get('/customers', [\App\Http\Controllers\CustomersController::class, 'index'])->name('client.customers.index');
        Route::get('/customers/{customer}', [\App\Http\Controllers\CustomersController::class, 'show'])->name('client.customers.show');
        Route::post('/customers/{customer}/message', [ConversationsController::class, 'startFromCustomer'])->name('client.customers.message');
        Route::post('/messages/{conversation}/add-customer', [ConversationsController::class, 'addCustomer'])->name('messages.add-customer');
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

        // AI Image Generation routes
        Route::get('/exercises/ai/credits', [ExercisesController::class, 'getAiCredits'])->name('exercises.ai.credits');
        Route::post('/exercises/ai/generate', [ExercisesController::class, 'generateImage'])->name('exercises.ai.generate');
        Route::post('/exercises/ai/store', [ExercisesController::class, 'storeFromAi'])->name('exercises.ai.store');

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
        Route::get('/sessions/{session}/pdf-preview', [SessionsController::class, 'showPdfPreview'])->name('sessions.pdf-preview');
        Route::post('/sessions/pdf-preview', [SessionsController::class, 'pdfPreview'])->name('sessions.pdf-preview-post');
        Route::post('/sessions/{session}/send-email', [SessionsController::class, 'sendEmail'])->name('sessions.send-email');
        Route::post('/sessions/{session}/duplicate', [SessionsController::class, 'duplicate'])->name('sessions.duplicate');

        // Session layouts routes
        Route::post('/sessions/{session}/layout', [SessionsController::class, 'saveLayout'])->name('sessions.layout.save');
        Route::post('/sessions/layout', [SessionsController::class, 'saveLayout'])->name('sessions.layout.save-new');
        Route::get('/sessions/{session}/layout', [SessionsController::class, 'getLayout'])->name('sessions.layout.get');
        Route::get('/sessions/{session}/layout/pdf', [SessionsController::class, 'pdfFromLayout'])->name('sessions.layout.pdf');
        Route::get('/sessions/layout/exercises', [SessionsController::class, 'getExercisesForLayout'])->name('sessions.layout.exercises');

        // Subscription routes
        Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('/subscription/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::get('/subscription/portal', [\App\Http\Controllers\SubscriptionController::class, 'portal'])->name('subscription.portal');
        Route::get('/subscription/success', [\App\Http\Controllers\SubscriptionController::class, 'success'])->name('subscription.success');

        // Profil coach public (édition)
        Route::get('/mon-profil-coach', [CoachProfileController::class, 'edit'])->name('coach.profile.edit');
        Route::post('/mon-profil-coach', [CoachProfileController::class, 'update'])->name('coach.profile.update');

        // Team routes
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::post('/team/{team}/leave', [TeamController::class, 'leave'])->name('team.leave');
        Route::post('/team/{team}/transfer-ownership/{member}', [TeamController::class, 'transferOwnership'])->name('team.transfer-ownership');
        Route::delete('/team/{team}', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::delete('/team/{team}/members/{member}', [TeamController::class, 'destroyMember'])->name('team.members.destroy');
        Route::post('/team/invitations', [TeamInvitationsController::class, 'store'])->name('team.invitations.store');
        Route::delete('/team/invitations/{invitation}', [TeamInvitationsController::class, 'destroy'])->name('team.invitations.destroy');
        Route::post('/team/invitations/{invitation}/accept', [TeamInvitationsController::class, 'acceptForUser'])->name('team.invitations.accept-user');
        Route::post('/team/invitations/{invitation}/decline', [TeamInvitationsController::class, 'declineForUser'])->name('team.invitations.decline-user');
        Route::post('/team/invitations/{token}/accept', [TeamInvitationsController::class, 'accept'])
            ->whereUuid('token')
            ->name('team.invitations.accept');

    }); // fin du groupe réservé aux coachs
});

// Team invitation landing page (public)
Route::get('/team/invitations/{token}', [TeamInvitationsController::class, 'show'])->name('team.invitations.show');

// Stripe
Route::post(
    '/stripe/webhook',
    [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']
)->name('cashier.webhook')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \App\Http\Middleware\HandleInertiaRequests::class,
        \App\Http\Middleware\HandleAppearance::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);

// Route publique pour consulter une séance via token
Route::get('/session/{shareToken}', [PublicSessionController::class, 'show'])
    ->name('public.session.show');
Route::get('/session/{shareToken}/pdf', [PublicSessionController::class, 'pdf'])
    ->name('public.session.pdf');

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

// Sitemap
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Protégées par token secret (MAINTENANCE_TOKEN dans .env)
// Routes GET également disponibles pour faciliter l'utilisation depuis le navigateur
Route::get('/maintenance/stripe-migrate-subscription', [ArtisanCommandController::class, 'migrateSubscription'])
    ->name('maintenance.stripe-migrate-subscription.get');

Route::get('/maintenance/storage-link', [ArtisanCommandController::class, 'storageLink'])
    ->name('maintenance.storage-link.get');

require __DIR__.'/settings.php';
