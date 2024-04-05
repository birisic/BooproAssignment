<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get("/popularity/{word}", [SearchController::class, "getWordPopularity"]);
