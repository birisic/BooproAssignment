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
            $user = "birisic";
            $repository = "BooproAssignment";
            $headers = [
                "Accept" => "application/vnd.github.text-match+json",
                "Authorization" => "Bearer $authorizationToken"
            ];

            $queryString = http_build_query([
                'q' => "$word repo:$user/$repository"
            ]);

//            var_dump($queryString);
//            die;

            $response = Http::withHeaders($headers)->get("$endpoint?$queryString");
            $contentType = $response->header('Content-Type');

            $output = [];
            foreach ($response->json()["items"] as $item) {
                $output[] = $item["text_matches"];
            }

            return response()->json($output)->header('Content-Type', $contentType);

        }





//        return $response;
    }
}
