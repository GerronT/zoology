<?php

use Illuminate\Http\Request;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/groups/search', [GroupController::class, 'search']);
Route::get('/groups/{group}/children', [GroupController::class, 'childgroups']);
