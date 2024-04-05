<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SearchController extends Controller
{
    public function getWordPopularity($word)
    {
        // validate word

        $token = env("GITHUB_PERSONAL_ACCESS_TOKEN");

        $response = Http::withHeaders([
            "Accept" => "application/vnd.github+json",
            "Authorization" => "Bearer $token"
        ])->withUrlParameters([
            'endpoint' => 'https://api.github.com/search/issues',
            'word' => $word
        ])->get("{+endpoint}?q={word}");



        return $response;
    }
}
