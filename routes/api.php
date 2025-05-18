<?php

use Illuminate\Http\Request;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RankingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/groups/search', [GroupController::class, 'search']);
Route::get('/groups/{group}/children-only', [GroupController::class, 'childgroups']);
Route::get('/groups/{group}/youngest-ranked-ancestor', [GroupController::class, 'youngestRankedAncestor']);

Route::get('/groups/tree', [GroupController::class, 'tree']);

Route::get('/groups/{group}', [GroupController::class, 'self']);
Route::get('/groups/{group}/children', [GroupController::class, 'children']);

Route::post('/move-group', [GroupController::class, 'moveGroup']);
Route::get('/ranks', [RankingController::class, 'index']);

