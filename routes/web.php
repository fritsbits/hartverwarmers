<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\DownloadsAndBookmarksController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\FicheController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\MailPreviewController;
use App\Http\Controllers\MyFichesController;
use App\Http\Controllers\ProfileController as HvProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ToolsInspirationController;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

// Sitemap
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Home
Route::get('/', HomeController::class)->name('home');

// Initiatives
Route::get('/initiatieven', [InitiativeController::class, 'index'])->name('initiatives.index');
Route::get('/initiatieven/{initiative:slug}', [InitiativeController::class, 'show'])->name('initiatives.show');

// Fiches
Route::get('/fiches-van-de-maand', [FicheController::class, 'ficheVanDeMaandArchive'])->name('fiches.ficheVanDeMaand');
Route::get('/initiatieven/{initiative:slug}/{fiche:slug}', [FicheController::class, 'show'])->name('fiches.show');
Route::get('/initiatieven/{initiative:slug}/{fiche:slug}/download', [FicheController::class, 'downloadFiles'])->name('fiches.download');

// Themes (placeholder)
Route::get('/themas', [ThemeController::class, 'index'])->name('themes.index');

// Contributors
Route::get('/bijdragers', [ContributorController::class, 'index'])->name('contributors.index');
Route::get('/bijdragers/{user}', [ContributorController::class, 'show'])->name('contributors.show');

// Favorieten & downloads
Route::get('/favorieten', DownloadsAndBookmarksController::class)->name('bookmarks.index');

// Mijn fiches
Route::get('/mijn-fiches', MyFichesController::class)->name('my-fiches.index');

// Profile (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profiel', [HvProfileController::class, 'show'])->name('profile.show');
    Route::put('/profiel', [HvProfileController::class, 'update'])->name('profile.update');
    Route::post('/profiel/avatar', [HvProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profiel/avatar', [HvProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::get('/profiel/beveiliging', [HvProfileController::class, 'security'])->name('profile.security');
    Route::redirect('/profiel/favorieten', '/favorieten', 301);
    Route::redirect('/profiel/fiches', '/mijn-fiches', 301);
    Route::post('/fiches/{fiche}/favoriet', [BookmarkController::class, 'toggle'])->name('fiches.bookmark');
    Route::post('/fiches/{fiche}/comment', [CommentController::class, 'store'])->name('fiches.comment');
    Route::post('/initiatieven/{initiative:slug}/comment', [CommentController::class, 'storeForInitiative'])->name('initiatives.comment');

    // Fiche creation & editing
    Route::get('/fiches/nieuw', [FicheController::class, 'create'])->name('fiches.create');
    Route::get('/fiches/{fiche:slug}/bewerken', [FicheController::class, 'edit'])->name('fiches.edit');

    // Impersonation (stop must be registered before start to avoid {user} wildcard matching "stop")
    Route::post('/admin/impersonate/stop', [ImpersonateController::class, 'stop'])->name('admin.impersonate.stop');

    // Admin actions
    Route::middleware('admin')->group(function () {
        Route::get('/admin/design-systeem', [DesignSystemController::class, 'index'])->name('admin.design-system');
        Route::get('/admin/features', [FeatureController::class, 'index'])->name('admin.features');
        Route::post('/admin/features/{feature}/toggle', [FeatureController::class, 'toggle'])->name('admin.features.toggle');
        Route::get('/admin/mails', [MailPreviewController::class, 'index'])->name('admin.mails');
        Route::get('/admin/mails/{email}', [MailPreviewController::class, 'show'])->name('admin.mails.show');
        Route::get('/admin/mails/{email}/preview', [MailPreviewController::class, 'preview'])->name('admin.mails.preview');
        Route::delete('/initiatieven/{initiative:slug}', [InitiativeController::class, 'destroy'])->name('initiatives.destroy');
        Route::post('/initiatieven/{initiative:slug}/{fiche:slug}/diamant', [FicheController::class, 'toggleDiamond'])->name('fiches.toggleDiamond');
        Route::post('/initiatieven/{initiative:slug}/{fiche:slug}/fiche-van-de-maand', [FicheController::class, 'setFicheOfMonth'])->name('fiches.setFicheOfMonth');
        Route::delete('/initiatieven/{initiative:slug}/{fiche:slug}/fiche-van-de-maand', [FicheController::class, 'unsetFicheOfMonth'])->name('fiches.unsetFicheOfMonth');
        Route::delete('/initiatieven/{initiative:slug}/{fiche:slug}', [FicheController::class, 'destroy'])->name('fiches.destroy');
        Route::get('/admin/gebruikers', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/impersonate/{user}', [ImpersonateController::class, 'start'])
            ->where('user', '[0-9]+')
            ->name('admin.impersonate.start');
    });
});

// Tools & Inspiratie
Route::get('/tools-en-inspiratie', [ToolsInspirationController::class, 'index'])->name('tools.index');
Route::get('/videolessen', [ToolsInspirationController::class, 'videoLessons'])->name('tools.videolessen');
Route::get('/workshops', [ToolsInspirationController::class, 'workshops'])->name('tools.workshops');
Route::get('/tools/{uid}', [ToolsInspirationController::class, 'showTool'])->name('tools.show');
Route::get('/workshops/{uid}', [ToolsInspirationController::class, 'showWorkshop'])->name('tools.workshops.show');
Route::get('/roadmap-verandertraject-wonen-en-leven', [ContentController::class, 'roadmap'])->name('content.roadmap');

// Goals (DIAMANT model)
Route::middleware(EnsureFeaturesAreActive::using('diamant-goals'))->group(function () {
    Route::get('/doelen', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/doelen/{facetSlug}', [GoalController::class, 'show'])->name('goals.show');
});

// Generic content (lessenreeks, wonen-en-leven)
Route::get('/{slug}', [ContentController::class, 'content'])
    ->where('slug', '(lessenreeks|wonen-en-leven).*')
    ->name('content');

// About
Route::view('/over-ons', 'about')->name('about');

// Legal pages
Route::view('/privacybeleid', 'legal.privacy')->name('legal.privacy');
Route::view('/gebruiksvoorwaarden', 'legal.terms')->name('legal.terms');
Route::view('/auteursrecht', 'legal.copyright')->name('legal.copyright');

// Legacy redirects (old /uitwerkingen URLs → /fiches)
Route::redirect('/uitwerkingen/nieuw', '/fiches/nieuw', 301);
Route::get('/uitwerkingen/{slug}/bewerken', fn (string $slug) => redirect("/fiches/{$slug}/bewerken", 301));

// Breeze auth routes
require __DIR__.'/auth.php';

// Preview error pages (remove before deploying)
Route::get('/preview/404', fn () => response(view('errors.404'), 404));
Route::get('/preview/403', fn () => response(view('errors.403'), 403));
Route::get('/preview/500', fn () => response(view('errors.500'), 500));
