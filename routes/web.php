<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Routes - Redirect to role-specific dashboard
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        if ($role === 'admin') {
            return redirect()->route('dashboard.admin');
        }
        return redirect()->route('dashboard.user');
    })->name('dashboard');

    // Admin Dashboard
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('dashboard.admin');

    Route::post('/admin/dashboard/reset', [DashboardController::class, 'resetAll'])
        ->middleware('role:admin')
        ->name('dashboard.reset');

    Route::post('/admin/settings', [DashboardController::class, 'saveSettings'])
        ->middleware('role:admin')
        ->name('settings.update');

    // User Dashboard
    Route::get('/user/dashboard', [DashboardController::class, 'user'])
        ->middleware('role:user,admin')
        ->name('dashboard.user');

    // Live data buat polling real-time (dipanggil lewat fetch() dari JS)
    Route::get('/dashboard/live-data', [DashboardController::class, 'liveData'])
        ->middleware('role:user,admin')
        ->name('dashboard.live-data');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';