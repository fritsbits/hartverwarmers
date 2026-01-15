<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController as HvProfileController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', HomeController::class)->name('home');

// Activities
Route::get('/activiteiten', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activiteiten/{activity}', [ActivityController::class, 'show'])->name('activities.show');
Route::get('/activiteiten/{activity}/print', [ActivityController::class, 'print'])->name('activities.print');

// Themes
Route::get('/themas', [ThemeController::class, 'index'])->name('themes.index');
Route::get('/themas/{theme}', fn() => redirect()->route('themes.index'));

// Authors
Route::get('/bijdragers', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/bijdragers/{author}', [AuthorController::class, 'show'])->name('authors.show');

// Profile (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profiel', [HvProfileController::class, 'show'])->name('profile.show');
    Route::get('/profiel/bookmarks', [HvProfileController::class, 'bookmarks'])->name('profile.bookmarks');
    Route::post('/activiteiten/{activity}/bookmark', [BookmarkController::class, 'toggle'])->name('activities.bookmark');
    Route::post('/activiteiten/{activity}/comment', [CommentController::class, 'store'])->name('activities.comment');
});

// Breeze auth routes
require __DIR__.'/auth.php';
