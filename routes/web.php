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

Route::prefix('animals')->group(function () {
    Route::get('/create', [AnimalController::class, 'create']);
    Route::post('/', [AnimalController::class, 'store']);
});

Route::prefix('groups')->as('groups.')->group(function () {
     // ---- Main Group Tree View ----
    Route::get('/tree/{group_root_id?}', [GroupController::class, 'groupTree'])->name('tree');

    // ---- Custom and Nested Routes ----
    Route::get('/{group}/children', [GroupController::class, 'children'])->name('children');
    Route::post('/move', [GroupController::class, 'move'])->name('move');

    // ---- Standard RESTful API Routes ----
    Route::get('/', [GroupController::class, 'index'])->name('index');            // GET /groups
    Route::post('/', [GroupController::class, 'store'])->name('store');           // POST /groups
    Route::get('/{group}', [GroupController::class, 'show'])->name('show');       // GET /groups/{group}
    Route::post('/{group}', [GroupController::class, 'update'])->name('update');  // POST /groups/{group}
    Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy'); // DELETE /groups/{group}

    // ---- Form/Editor Views ----
    Route::get('/create/{group}', [GroupController::class, 'create'])->name('create');
    Route::get('/edit/{group}', [GroupController::class, 'edit'])->name('edit');

    // ---- Mass Create View/Store ----
    Route::get('/mass-create', [GroupController::class, 'massCreate'])->name('massCreate');
    Route::post('/mass-store', [GroupController::class, 'massStore'])->name('massStore');
});

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
