<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'create'])->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/classroom/create', [DashboardController::class, 'createClassroom'])
    ->middleware(['auth', 'verified'])
    ->name('classroom.create');

Route::post('/classroom/join', [DashboardController::class, 'joinClassroom'])
    ->middleware(['auth', 'verified'])
    ->name('classroom.join');

Route::get('/user/profile', [ProfileController::class, 'create'])->middleware(['auth', 'verified'])->name('user.profile');

Route::post('/user/update', [ProfileController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('user.update');

Route::post('/user/password', [ProfileController::class, 'changePassword'])
    ->middleware(['auth', 'verified'])
    ->name('user.password');

require __DIR__ . '/auth.php';
