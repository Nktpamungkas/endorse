<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\EndorsementRevisionController;
use App\Http\Controllers\UserManageController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::middleware('single.auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('endorsements', EndorsementController::class);
    Route::post('/endorsements/{endorsement}/revisions', [EndorsementRevisionController::class, 'store'])
        ->name('endorsements.revisions.store');
    Route::delete('/endorsements/{endorsement}/revisions/{revision}', [EndorsementRevisionController::class, 'destroy'])
        ->name('endorsements.revisions.destroy');
    Route::post('/endorsements/{endorsement}/status', [EndorsementController::class, 'updateStatus'])
        ->name('endorsements.status.update');
    Route::get('/endorsements-export', [EndorsementController::class, 'export'])->name('endorsements.export');
    Route::get('/users', [UserManageController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManageController::class, 'store'])->name('users.store');
    Route::post('/users/{user}', [UserManageController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManageController::class, 'destroy'])->name('users.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
