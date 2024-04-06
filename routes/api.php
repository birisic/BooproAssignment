<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get("/score/{word}/{platform?}",
    [SearchController::class, "getWordPopularity"]);//->middleware('auth:sanctum');
