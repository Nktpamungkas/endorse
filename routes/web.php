<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\EndorsementRevisionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\TotalModalController;
use App\Http\Controllers\UserManageController;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/', LandingController::class)->name('landing');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');

Route::get('/ui-demo', function (): Illuminate\View\View {
    return view('ui-demo');
})->name('ui.demo');

Route::middleware('single.auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/pemasukan', [PemasukanController::class, 'index'])->name('pemasukan.index');
    Route::post('/pemasukan', [PemasukanController::class, 'store'])->name('pemasukan.store');
    Route::put('/pemasukan/{pemasukan}', [PemasukanController::class, 'update'])->name('pemasukan.update');
    Route::delete('/pemasukan/{pemasukan}', [PemasukanController::class, 'destroy'])->name('pemasukan.destroy');
    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
    Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('pengeluaran.store');
    Route::put('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'update'])->name('pengeluaran.update');
    Route::delete('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'destroy'])->name('pengeluaran.destroy');
    Route::get('/saldo', SaldoController::class)->name('saldo.index');
    Route::get('/total-modal', TotalModalController::class)->name('total-modal.index');
    Route::resource('endorsements', EndorsementController::class);
    Route::get('/endorsements-deleted', [EndorsementController::class, 'trashed'])->name('endorsements.trashed');
    Route::get('/endorsements-deleted/{endorsement}', [EndorsementController::class, 'trashedShow'])->name('endorsements.trashed.show');
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
    Route::post('/users/{user}/force-logout', [UserManageController::class, 'forceLogout'])->name('users.forceLogout');
    Route::delete('/users/{user}', [UserManageController::class, 'destroy'])->name('users.destroy');
    Route::get('/database-backups', [DatabaseBackupController::class, 'index'])->name('database-backups.index');
    Route::post('/database-backups/settings', [DatabaseBackupController::class, 'update'])->name('database-backups.update');
    Route::post('/database-backups/run', [DatabaseBackupController::class, 'runNow'])->name('database-backups.run');
    Route::get('/database-backups/{backupLog}/download', [DatabaseBackupController::class, 'download'])->name('database-backups.download');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile/password', [AuthController::class, 'showPasswordForm'])->name('password.form');
    Route::post('/profile/password', [AuthController::class, 'updatePassword'])->name('password.update');
});
