<?php

namespace App\Http\Controllers;

use App\Enums\SearchProviderEnum;
use App\Models\Context;
use App\Models\SearchProvider;
use App\Models\Word;
use App\Services\GitHubService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function getWordPopularity($word, $platform = "github")
    {
        //validate word & platform

        $provider = strtolower(trim($platform));

        if ($provider === strtolower(SearchProviderEnum::GITHUB->value)) {
            $authorizationToken = env("GITHUB_PERSONAL_ACCESS_TOKEN");
            $endpoint = "https://api.github.com/search/issues";
            $username = "";
            $repository = "";
            $type = "";
            $headers = [
                "Accept" => "application/vnd.github.text-match+json",
                "Authorization" => "Bearer $authorizationToken"
            ];
            $numOfPages = 2; //number of pages to load from GitHub api ($numOfPages * per_page ~= $numOfResults)
            $itemsPerPage = 100;

            try {
                // check if there is a record in the database for this word, in the given context, for the given
                // number of pages and number of items par each page. if there is, then check if it was updated
                // within the last hour. If that's the case, then retrieve the values from the database.
                // Otherwise, search again and recalculate the score.

                // samo ovde proveri za vreme, jer ako se udje u ponovno pretrazivanje i izracunavanje ocene nema potrebe
                // ponovo proveravati za vreme, ako znamo iz ove provere da uslov nije zadovoljen

                $providerId = SearchProvider::where("name", "GitHub")->value("id");

                if (!isset($providerId)) {
                    throw new \Exception("Provider ID not found.");
                }

                $wordId = Word::where("name", $word)->value("id");
                $contextId = Context::where([
                    "name" => $repository !== '' ? $repository : null,
                    "owner_username" => $username !== '' ? $username : null,
                    "type" => $type !== '' ? $type : null,
                    "provider_id" => $providerId,
                ])->value("id");

                $searchRecord = DB::table("searches")->where([
                    "word_id" => $wordId,
                    "context_id" => $contextId,
                    "count_pages" => $numOfPages,
                    "items_per_page" => $itemsPerPage
                ])->first();

                $arrOutput = [];
                if ($searchRecord){
                    $updatedAt = Carbon::parse($searchRecord->updated_at);
                    $threshold = Carbon::now()->subHour();
//                    var_dump($threshold); die;

                    if ($updatedAt->greaterThanOrEqualTo($threshold)) {

                        $counterTotal = $searchRecord->count_positive + $searchRecord->count_negative;
                        $score = 0;

                        if ($counterTotal != 0) {
                            $score = ($searchRecord->count_positive / $counterTotal) * 10;
                        }

                        $arrOutput = [
                            "term" => $word,
                            "positiveCount" => $searchRecord->count_positive,
                            "negativeCount" => $searchRecord->count_negative,
                            "total" => $searchRecord->count_positive + $searchRecord->count_negative,
                            "score" => round($score, 2)
                        ];
                    }
                    else {
                        $gitHubService = new GitHubService($word, $endpoint, $username, $repository, $type, $headers, $numOfPages, $itemsPerPage);
                        $httpResponseItems = $gitHubService->search();
//                        return $httpResponseItems;

                        $arrOutput = $gitHubService->calcPopularityScore($httpResponseItems);
                    }
                }
                else {
                    $gitHubService = new GitHubService($word, $endpoint, $username, $repository, $type, $headers, $numOfPages, $itemsPerPage);
                    $httpResponseItems = $gitHubService->search();
//                        return $httpResponseItems;

                    $arrOutput = $gitHubService->calcPopularityScore($httpResponseItems);
                }


                return response()->json($arrOutput)->header('Content-Type', "application/json");
            }
            catch (\Exception $e){
                Log::error("Exception error message: " . $e->getMessage());
                return "An error occurred on the server.";
            }
        }
        else if ($provider === strtolower(SearchProviderEnum::X->value)){
            return "x";
        }

        return "Other platform.";
    }
}
