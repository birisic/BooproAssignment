<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use function Laravel\Prompts\text;


class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        //validate word

        //check in the database if there were previous searches for the given word in the given context

        if ($platform === "github") { //use enum
            $authorizationToken = env("GITHUB_PERSONAL_ACCESS_TOKEN");
            $endpoint = "https://api.github.com/search/issues";
            $user = "";
            $repository = "";
            $headers = [
                "Accept" => "application/vnd.github.text-match+json",
                "Authorization" => "Bearer $authorizationToken"
            ];

            try {
                $gHubService = new GitHubService($word, $endpoint, $authorizationToken, $user, $repository, $headers);
                $response = $gHubService->search();

                //return $response;
                $contentType = $response->header('Content-Type');

                if (!isset($response->json()["items"])) {
                    throw new Exception("No 'items' array retrieved from the response.");
                }

                $arrOfStrings = [];
                foreach ($response->json()["items"] as $issue) {
                    foreach ($issue["text_matches"] as $textMatch) {
                        $text = strtolower(str_replace(["\n", "\t", ""], "", $textMatch["fragment"]));

                        if (preg_match("/\b(?:$word\s*(rocks|sucks))\b/i", $text)){
                            $arrOfStrings[] = $text;
                        }
                    }
                }

                $counterPositive = 0;
                $counterNegative = 0;
                $arrOfWords = [];

                foreach ($arrOfStrings as $string) {
                    $words = array_filter(explode(" ", $string)); //remove extra spaces
                    $arrOfWords[] = array_values($words);
                }


                foreach ($arrOfWords as $words) {
                    foreach ($words as $key=>$value) {
                        if ($value === $word){
                            if ($words[$key + 1] === "rocks"){
                                $counterPositive++;
                            }
                            else if ($words[$key + 1] === "sucks"){
                                $counterNegative++;
                            }
                        }
                    }
                }

                $counterTotal = $counterPositive + $counterNegative;
                $score = 0;

                if ($counterTotal != 0) {
                    $score = ($counterPositive / $counterTotal) * 10;
                }

                // insert/update in the database
//                Word::create(["name", $word]);

                $output = [
                    "term" => $word,
                    "positiveCount" => $counterPositive,
                    "negativeCount" => $counterNegative,
                    "total" => $counterTotal,
                    "score" => round($score, 2)
                ];

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
