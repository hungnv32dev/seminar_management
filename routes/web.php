<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckInController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes (assuming Laravel Breeze/Jetstream is used)
require __DIR__.'/auth.php';

// Dashboard routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Dashboard API endpoints
    Route::prefix('api/dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/overview', [App\Http\Controllers\DashboardController::class, 'getOverviewStats'])->name('overview');
        Route::get('/workshops', [App\Http\Controllers\DashboardController::class, 'getWorkshopStats'])->name('workshops');
        Route::get('/participants', [App\Http\Controllers\DashboardController::class, 'getParticipantStats'])->name('participants');
        Route::get('/revenue', [App\Http\Controllers\DashboardController::class, 'getRevenueStats'])->name('revenue');
        Route::get('/trends', [App\Http\Controllers\DashboardController::class, 'getTrendData'])->name('trends');
        Route::get('/activity', [App\Http\Controllers\DashboardController::class, 'getRecentActivity'])->name('activity');
        Route::get('/comparison', [App\Http\Controllers\DashboardController::class, 'getWorkshopComparison'])->name('comparison');
        Route::get('/updates', [App\Http\Controllers\DashboardController::class, 'getRealTimeUpdates'])->name('updates');
        Route::get('/export', [App\Http\Controllers\DashboardController::class, 'exportData'])->name('export');
        Route::post('/clear-cache', [App\Http\Controllers\DashboardController::class, 'clearCache'])->name('clear-cache');
        Route::get('/config', [App\Http\Controllers\DashboardController::class, 'getConfig'])->name('config');
    });
});

// Check-in routes
Route::middleware(['auth'])->group(function () {
    Route::prefix('checkin')->name('checkin.')->group(function () {
        Route::get('/', [CheckInController::class, 'index'])->name('index');
        Route::get('/mobile', [CheckInController::class, 'mobile'])->name('mobile');
        Route::post('/scan', [CheckInController::class, 'scan'])->name('scan');
        Route::post('/manual/{participant}', [CheckInController::class, 'manualCheckIn'])->name('manual');
        Route::post('/undo/{participant}', [CheckInController::class, 'undoCheckIn'])->name('undo');
        Route::get('/participant', [CheckInController::class, 'getParticipant'])->name('participant');
        Route::get('/statistics/{workshop}', [CheckInController::class, 'getStatistics'])->name('statistics');
        Route::post('/bulk', [CheckInController::class, 'bulkCheckIn'])->name('bulk');
        Route::get('/export/{workshop}', [CheckInController::class, 'exportReport'])->name('export');
        Route::get('/search', [CheckInController::class, 'search'])->name('search');
    });
});

// Placeholder routes for other controllers (to be implemented in other tasks)
Route::middleware(['auth'])->group(function () {
    Route::resource('workshops', 'WorkshopController');
    Route::resource('participants', 'ParticipantController');
    Route::resource('users', 'UserController');
    Route::resource('roles', 'RoleController');
    Route::resource('email-templates', 'EmailTemplateController');
});
