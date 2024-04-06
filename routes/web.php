<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get("/popularity/{word}/{platform?}", [SearchController::class, "getWordPopularity"]);
