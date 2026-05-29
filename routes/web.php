<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'create'])->name('home');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/sintak', [DashboardController::class, 'sintak'])->name('dashboard.sintak');
    Route::get('/dashboard/progress', [DashboardController::class, 'progress'])->name('dashboard.progress');
    Route::get('/dashboard/help', [DashboardController::class, 'help'])->name('dashboard.help');
    Route::get('/dashboard/users', [DashboardController::class, 'users'])->name('dashboard.users');
    Route::post('/dashboard/users', [DashboardController::class, 'storeUser'])->name('dashboard.users.store');
    Route::put('/dashboard/users/{managedUser}', [DashboardController::class, 'updateUser'])->name('dashboard.users.update');
    Route::delete('/dashboard/users/{managedUser}', [DashboardController::class, 'destroyUser'])->name('dashboard.users.destroy');
    Route::post('/dashboard/projects/{proyek}', [DashboardController::class, 'updateProject'])->name('dashboard.projects.update');
    Route::post('/dashboard/projects/{proyek}/reflection', [DashboardController::class, 'updateReflection'])->name('dashboard.reflection.update');
    Route::post('/dashboard/projects/{proyek}/stages/{sintak}', [DashboardController::class, 'updateStage'])->name('dashboard.stages.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
