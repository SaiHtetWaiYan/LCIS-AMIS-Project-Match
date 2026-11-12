<?php

use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserDeactivationController;
use App\Http\Controllers\AmisContractController;
use App\Http\Controllers\LcisProjectController;
use App\Http\Controllers\MatchTransitionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectMatchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::get('/lcis-projects', [LcisProjectController::class, 'index'])->name('lcis-projects.index');
    Route::get('/lcis-projects/{lcisProject}', [LcisProjectController::class, 'show'])->name('lcis-projects.show');
    Route::get('/amis-contracts', [AmisContractController::class, 'index'])->name('amis-contracts.index');
    Route::get('/amis-contracts/{amisContract}', [AmisContractController::class, 'show'])->name('amis-contracts.show');

    Route::post('/matches', [ProjectMatchController::class, 'store'])->name('matches.store');
    Route::get('/matches/{match}', [ProjectMatchController::class, 'show'])->name('matches.show');
    Route::post('/matches/{match}/confirm', [MatchTransitionController::class, 'confirm'])->name('matches.confirm');
    Route::post('/matches/{match}/reject', [MatchTransitionController::class, 'reject'])->name('matches.reject');
    Route::post('/matches/{match}/withdraw', [MatchTransitionController::class, 'withdraw'])->name('matches.withdraw');
    Route::post('/matches/{match}/unmatch', [MatchTransitionController::class, 'unmatch'])->name('matches.unmatch');
    Route::post('/matches/{match}/review', [MatchTransitionController::class, 'review'])->name('matches.review');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/deactivation', [UserDeactivationController::class, 'store'])->name('users.deactivation.store');
        Route::delete('users/{user}/deactivation', [UserDeactivationController::class, 'destroy'])->name('users.deactivation.destroy');

        Route::get('sync', [SyncController::class, 'index'])->name('sync.index');
        Route::post('sync/{source}', [SyncController::class, 'store'])->name('sync.store');
    });
});

require __DIR__.'/auth.php';
