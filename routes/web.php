<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\GroupController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/home', function () {
    return Inertia::render('Home/Homepage', [
        'classifications' => \App\Models\Classification::all(),
        'levels' => \App\Models\Level::all(),
        'groups' => \App\Models\Group::all(),
        'animals' => \App\Models\Animal::all()
    ]);
});

Route::get('/animals/create', [AnimalController::class, 'create']);
Route::post('/animals', [AnimalController::class, 'store']);



Route::get('/groups/create', [GroupController::class, 'create']);
Route::post('/groups', [GroupController::class, 'store']);
Route::get('/groups/tree/{group_root_id?}', [GroupController::class, 'indexTree']);

Route::post('/groups/create-child', [GroupController::class, 'createChild']);
Route::post('/groups/{group}/update', [GroupController::class, 'update']);
Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
