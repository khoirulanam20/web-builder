<?php

use App\Http\Controllers\GenerateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublishController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [GenerateController::class, 'create'])->name('projects.create');
    Route::post('/projects', [GenerateController::class, 'store'])->name('projects.store');
    Route::get('/projects/import-code', [GenerateController::class, 'importCode'])->name('projects.import-code');
    Route::post('/projects/import-code', [GenerateController::class, 'storeFromCode'])->name('projects.import-code.store');
    Route::get('/projects/json-prompt', [GenerateController::class, 'jsonPrompt'])->name('projects.json-prompt');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('/preview/{project}', [PreviewController::class, 'show'])->name('projects.preview');
    Route::post('/projects/{project}/publish', [PublishController::class, 'store'])->name('projects.publish');
    Route::put('/projects/{project}/code', [ProjectUpdateController::class, 'updateCode'])->name('projects.update-code');
    Route::post('/projects/{project}/improve', [ProjectUpdateController::class, 'improve'])->name('projects.improve');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
