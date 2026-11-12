<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserDeactivationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/deactivation', [UserDeactivationController::class, 'store'])->name('users.deactivation.store');
        Route::delete('users/{user}/deactivation', [UserDeactivationController::class, 'destroy'])->name('users.deactivation.destroy');
    });
});

require __DIR__.'/auth.php';
