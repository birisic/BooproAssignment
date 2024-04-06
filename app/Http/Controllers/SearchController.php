<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        // validate word

        if ($platform === "github"){ // use enum
            $authorizationToken = env("GITHUB_PERSONAL_ACCESS_TOKEN");
            $endpoint = "https://api.github.com/search/issues";
            $headers = [
                "Accept" => "application/vnd.github.text-match+json",
                "Authorization" => "Bearer $authorizationToken"
            ];

            $queryString = http_build_query([
                'q' => $word . ' user:birisic'
            ]);

//            dd($queryString);

            return Http::withHeaders($headers)
                ->withUrlParameters([
                    'endpoint' => $endpoint,
                    'word' => $word
                ])->get("{+endpoint}?$queryString");
        }





//        return $response;
    }
}
