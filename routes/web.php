<?php

use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserDeactivationController;
use App\Http\Controllers\AmisContractController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LcisProjectController;
use App\Http\Controllers\MatchExportController;
use App\Http\Controllers\MatchQueueController;
use App\Http\Controllers\MatchTransitionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectMatchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/guide', GuideController::class)->name('guide');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::get('/lcis-projects', [LcisProjectController::class, 'index'])->name('lcis-projects.index');
    Route::get('/lcis-projects/{lcisProject}', [LcisProjectController::class, 'show'])->name('lcis-projects.show');
    Route::get('/amis-contracts', [AmisContractController::class, 'index'])->name('amis-contracts.index');
    Route::get('/amis-contracts/{amisContract}', [AmisContractController::class, 'show'])->name('amis-contracts.show');

    Route::get('/compare', [CompareController::class, 'show'])->name('compare');
    Route::get('/queue', MatchQueueController::class)->name('matches.queue');

    Route::get('/matches', [ProjectMatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/export', MatchExportController::class)->name('matches.export');
    Route::post('/matches', [ProjectMatchController::class, 'store'])->name('matches.store');
    Route::get('/matches/{match}', [ProjectMatchController::class, 'show'])->whereNumber('match')->name('matches.show');
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
