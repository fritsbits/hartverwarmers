<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\FicheController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\ProfileController as HvProfileController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ToolsInspirationController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', HomeController::class)->name('home');

// Initiatives
Route::get('/initiatieven', [InitiativeController::class, 'index'])->name('initiatives.index');
Route::get('/initiatieven/{initiative:slug}', [InitiativeController::class, 'show'])->name('initiatives.show');

// Fiches (nested under initiative)
Route::get('/initiatieven/{initiative:slug}/{fiche:slug}', [FicheController::class, 'show'])->name('fiches.show');

// Themes (placeholder)
Route::get('/themas', [ThemeController::class, 'index'])->name('themes.index');

// Contributors
Route::get('/bijdragers', [ContributorController::class, 'index'])->name('contributors.index');
Route::get('/bijdragers/{user}', [ContributorController::class, 'show'])->name('contributors.show');

// Profile (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profiel', [HvProfileController::class, 'show'])->name('profile.show');
    Route::put('/profiel', [HvProfileController::class, 'update'])->name('profile.update');
    Route::post('/profiel/avatar', [HvProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profiel/avatar', [HvProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::get('/profiel/beveiliging', [HvProfileController::class, 'security'])->name('profile.security');
    Route::get('/profiel/favorieten', [HvProfileController::class, 'bookmarks'])->name('profile.bookmarks');
    Route::post('/fiches/{fiche}/favoriet', [BookmarkController::class, 'toggle'])->name('fiches.bookmark');
    Route::post('/fiches/{fiche}/comment', [CommentController::class, 'store'])->name('fiches.comment');
    Route::post('/initiatieven/{initiative:slug}/comment', [CommentController::class, 'storeForInitiative'])->name('initiatives.comment');

    // Admin actions
    Route::middleware('admin')->group(function () {
        Route::delete('/initiatieven/{initiative:slug}', [InitiativeController::class, 'destroy'])->name('initiatives.destroy');
        Route::post('/initiatieven/{initiative:slug}/{fiche:slug}/diamant', [FicheController::class, 'toggleDiamond'])->name('fiches.toggleDiamond');
        Route::delete('/initiatieven/{initiative:slug}/{fiche:slug}', [FicheController::class, 'destroy'])->name('fiches.destroy');
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
Route::get('/doelen', [GoalController::class, 'index'])->name('goals.index');
Route::get('/doelen/{facetSlug}', [GoalController::class, 'show'])->name('goals.show');

// Generic content (lessenreeks, wonen-en-leven)
Route::get('/{slug}', [ContentController::class, 'content'])
    ->where('slug', '(lessenreeks|wonen-en-leven).*')
    ->name('content');

// Breeze auth routes
require __DIR__.'/auth.php';
