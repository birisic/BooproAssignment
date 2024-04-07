<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\text;


class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        //validate word

        if ($platform === "github") { //use enum
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

                //separate into a function
                $contentType = $response->header('Content-Type');
//                return $response;//response()->json($response)->header('Content-Type', $contentType);

                $strings = [];
                foreach ($response->json()["items"] as $issue) {
                    foreach ($issue["text_matches"] as $textMatch) {
                        $text = strtolower(str_replace(["\n", "\t", ""], "", $textMatch["fragment"]));
//                        $strings[] = $text;

                        if (preg_match("/\b(?:php\s*(rocks|sucks))\b/i", $text)){
                            $strings[] = $text;
                        }
                    }
                }

                $counterPositive = 0;
                $counterNegative = 0;
                $arrOfWords = [];
                foreach ($strings as $string) {
                    $words = array_filter(explode(" ", $string)); //remove extra spaces
                    $arrOfWords[] = array_values($words); //use only values
                }

//                return $arrOfWords;

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

                if ($counterTotal != 0) {
                    $score = ($counterPositive / $counterTotal) * 10;
                }
                else {
                    $score = 0;
                }

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
