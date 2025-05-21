<?php

use Illuminate\Http\Request;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RankingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// For mass-group create group dropdowns/filters
Route::prefix('groups')->group(function () {
    Route::get('/search', [GroupController::class, 'search']);
    Route::get('/{group}/children', [GroupController::class, 'childrenRaw']);
    Route::get('/{group}/youngest-ranked-ancestor', [GroupController::class, 'youngestRankedAncestor']);
});

// For Vuex store
Route::get('/ranks', [RankingController::class, 'index']);

