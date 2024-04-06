<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        // validate word

        if ($platform === "github") { // use enum
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

            try {
                $response = Http::withHeaders($headers)->get("$endpoint?$queryString");
                if (!isset($response)){
                    throw new \Exception("Response was not set.");
                }

                $contentType = $response->header('Content-Type');
                $output = [];
                foreach ($response->json()["items"] as $item) {
                    $output[] = $item["text_matches"];
                }

                return response()->json($output)->header('Content-Type', $contentType);
            }
            catch (\Exception $e){
                Log::error("Http response error: " . $e->getMessage());
                return "An error occurred on the server.";
            }
        }

        return "Other platform.";
    }
}
