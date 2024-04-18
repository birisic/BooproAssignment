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
    // fields
    private string $authorizationToken;
    private string $endpoint;
    private string $username;
    private string $contextName;
    private string $type;
    private array $headers;
    private int $numOfPages = 2;
    private int $itemsPerPage = 100;
    // end fields

    // methods
    public function getWordPopularity($word, $platform = "github"): string
    {
        //validate word & platform

        $provider = strtolower(trim($platform));

        if ($provider === strtolower(SearchProviderEnum::GITHUB->value)) {
            try {
                $providerId = SearchProvider::getProviderId(SearchProviderEnum::GITHUB->value);

                if (!isset($providerId)) {
                    throw new \Exception("Provider ID not found.");
                }

                $this->authorizationToken = env("GITHUB_PERSONAL_ACCESS_TOKEN");
                $this->endpoint = "https://api.github.com/search/issues";
                $this->username = "";
                $this->contextName = "";
                $this->type = "";
                $this->headers = [
                    "Accept" => "application/vnd.github.text-match+json",
                    "Authorization" => "Bearer " . $this->authorizationToken
                ];

                $searchRecord = $this->getSearchRecord($word, $providerId);

                if ($searchRecord){
                    $arrOutput = $this->calcPopularityScoreOrSearchAgain($searchRecord, $word);
                }
                else {
                    $arrOutput = $this->searchAndModifyDatabase($word);
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

    private function searchAndModifyDatabase($word): array
    {
        try {
            $gitHubService = new GitHubService($word, $this->endpoint, $this->username, $this->contextName,
                                               $this->type, $this->headers, $this->numOfPages, $this->itemsPerPage);
            $httpResponseItems = $gitHubService->search();

            return $gitHubService->calcPopularityScore($httpResponseItems);
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    private function calcPopularityScore($searchRecord, $word): array
    {
        $positiveCount = $searchRecord->count_positive;
        $negativeCount = $searchRecord->count_negative;
        $totalCount = $positiveCount + $negativeCount;
        $score = 0;

        if ($totalCount != 0) {
            $score = ($positiveCount / $totalCount) * 10;
        }

        return [
            "term" => $word,
            "positiveCount" => $positiveCount,
            "negativeCount" => $negativeCount,
            "total" => $totalCount,
            "score" => round($score, 2)
        ];
    }

    private function calcPopularityScoreOrSearchAgain($searchRecord, $word): array
    {
        $updatedAt = Carbon::parse($searchRecord->updated_at);
        $threshold = Carbon::now()->subHour();

        try {
            if ($updatedAt->greaterThanOrEqualTo($threshold)) {
                $arrOutput = $this->calcPopularityScore($searchRecord, $word);
            }
            else {
                $arrOutput = $this->searchAndModifyDatabase($word);
            }

            return $arrOutput;
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    private function getSearchRecord($word, int $providerId): ?object
    {
        $wordId = Word::where("name", $word)->value("id");
        $contextId = Context::where([
            "name" => $this->contextName !== '' ? $this->contextName : null,
            "owner_username" => $this->username !== '' ? $this->username : null,
            "type" => $this->type !== '' ? $this->type : null,
            "provider_id" => $providerId,
        ])->value("id");

        return DB::table("searches")->where([
            "word_id" => $wordId,
            "context_id" => $contextId,
            "count_pages" => $this->numOfPages,
            "items_per_page" => $this->itemsPerPage
        ])->first();
    }
    // end methods
}
