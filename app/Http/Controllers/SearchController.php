<?php

namespace App\Http\Controllers;

use App\Enums\SearchProviderEnum;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        //validate word

        //check in the database if there were previous searches for the given word in the given context

        $provider = strtolower(trim($platform));

        if ($provider === strtolower(SearchProviderEnum::GITHUB->value)) {
            $authorizationToken = env("GITHUB_PERSONAL_ACCESS_TOKEN");
            $endpoint = "https://api.github.com/search/issues";
            $user = "";
            $repository = "";
            $headers = [
                "Accept" => "application/vnd.github.text-match+json",
                "Authorization" => "Bearer $authorizationToken"
            ];

            try {
                $gitHubService = new GitHubService($word, $endpoint, $authorizationToken, $user, $repository, $headers);
                $httpResponseItems = $gitHubService->search();
//                return $httpResponseItems;

//                $contentType = $httpResponse->header('Content-Type');

                // check if there is a record in the database for this word and in the given context.
                // if there is, then check if it was updated within the last hour. If that's the case,
                // then retrieve the values from the database. Otherwise, recalculate the score.
                $arrOutput = [];


                $arrOutput = $gitHubService->calcPopularityScore($httpResponseItems);//["items"]);

                return response()->json($arrOutput)->header('Content-Type', "application/json");
            }
            catch (\Exception $e){
                Log::error("Http response error: " . $e->getMessage());
                return "An error occurred on the server.";
            }
        }
        else if ($provider === strtolower(SearchProviderEnum::X->value)){
            return "x";
        }

        return "Other platform.";
    }
}
