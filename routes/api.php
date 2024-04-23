<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get("/login", function(){
    return "login page";
})->name("login");

Route::get("/score/{word}/{platform?}",
    [SearchController::class, "getWordPopularity"])
    ->middleware("client");


