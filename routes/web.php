<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MonthlySubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::resource('departments', DepartmentController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        '/departments/{department}/deactivate',
        [DepartmentController::class, 'deactivate']
    )->name('departments.deactivate');

    Route::resource('users', UserController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        '/users/{user}/deactivate',
        [UserController::class, 'deactivate']
    )->name('users.deactivate');

    Route::patch(
        '/users/{user}/activate',
        [UserController::class, 'activate']
    )->name('users.activate');

    Route::patch(
        '/users/{user}/approve',
        [UserController::class, 'approve']
    )->name('users.approve');

    Route::prefix('submissions')
        ->name('submissions.')
        ->controller(MonthlySubmissionController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');

            Route::get('/create', 'create')->name('create');

            Route::post('/', 'store')->name('store');

            Route::get('/{submission}', 'show')->name('show');

            Route::post('/{submission}/submit', 'submit')
                ->name('submit');

            Route::post('/{submission}/approve', 'approve')
                ->name('approve');

            Route::post('/{submission}/reject', 'reject')
                ->name('reject');
        });

    Route::prefix('submissions/{submission}/documents')
        ->name('documents.')
        ->controller(DocumentController::class)
        ->group(function () {

            Route::post('/', 'store')
                ->name('store');

        });

    Route::prefix('documents')
        ->name('documents.')
        ->controller(DocumentController::class)
        ->group(function () {

            Route::get('/{document}/download', 'download')
                ->name('download');

            Route::delete('/{document}', 'destroy')
                ->name('destroy');

        });

});
