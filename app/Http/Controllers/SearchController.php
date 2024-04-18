<?php

namespace App\Http\Controllers;

use App\Enums\SearchProviderEnum;
use App\Interfaces\SearchableInterface;
use App\Models\Context;
use App\Models\SearchProvider;
use App\Models\Word;
use App\Services\AbstractSearchProviderService;
use App\Services\GitHubService;
use App\Services\XService;
use Carbon\Carbon;
use Dotenv\Parser\Parser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;

class SearchController extends Controller
{
    // fields
    private string $word;
    private int $numOfPages = 2;
    private int $itemsPerPage = 100;
    private SearchableInterface $serviceInstance;
    // end fields

    // methods
    public function getWordPopularity($word, $platform = "github"): string
    {
        //validate word & platform

        $provider = null;
        foreach (SearchProviderEnum::cases() as $providerName) {
            if (strtolower(trim($platform)) === strtolower($providerName->value)) {
                $provider = strtolower(trim($platform));
                break;
            }
        }

        if (!isset($provider)) {
            return "Platform not supported.";
        }

        try {
            $providerId = SearchProvider::getProviderId($provider);

            if (!isset($providerId)) {
                throw new \Exception("Provider ID not found for provider name: " . $provider);
            }

            $this->word = $word;

            // Make service instance
            $this->serviceInstance = $this->getServiceInstance($provider);

            $searchRecord = $this->getSearchRecord($providerId);

            if ($searchRecord) {
                $arrOutput = $this->calcPopularityScoreOrSearchAgain($searchRecord);
            } else {
                $arrOutput = $this->searchAndModifyDatabase();
            }

            return response()->json($arrOutput)->header('Content-Type', "application/json");
        } catch (\Exception $e) {
            Log::error("Exception error message: " . $e->getMessage());
            return "An error occurred on the server.";
        }

    }

    private function searchAndModifyDatabase(): array
    {
        try {
            $httpResponseItems = $this->serviceInstance->search();

            return $this->serviceInstance->calcPopularityScore($httpResponseItems);
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    private function calcPopularityScore($searchRecord): array
    {
        $positiveCount = $searchRecord->count_positive;
        $negativeCount = $searchRecord->count_negative;
        $totalCount = $positiveCount + $negativeCount;
        $score = 0;

        if ($totalCount != 0) {
            $score = ($positiveCount / $totalCount) * 10;
        }

        return [
            "term" => $this->word,
            "positiveCount" => $positiveCount,
            "negativeCount" => $negativeCount,
            "total" => $totalCount,
            "score" => round($score, 2)
        ];
    }

    private function calcPopularityScoreOrSearchAgain($searchRecord): array
    {
        $updatedAt = Carbon::parse($searchRecord->updated_at);
        $threshold = Carbon::now()->subHour();

        try {
            if ($updatedAt->greaterThanOrEqualTo($threshold)) {
                $arrOutput = $this->calcPopularityScore($searchRecord);
            }
            else {
                $arrOutput = $this->searchAndModifyDatabase();
            }

            return $arrOutput;
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    private function getSearchRecord(int $providerId): ?object
    {
        $instance = $this->serviceInstance;
        $wordId = Word::where("name", $this->word)->value("id");
        $contextId = Context::where([
            "name" => $instance->getContextName() !== '' ? $instance->getContextName() : null,
            "owner_username" => $instance->getUsername() !== '' ? $instance->getUsername() : null,
            "type" => $instance->getType() !== '' ? $instance->getType() : null,
            "provider_id" => $providerId,
        ])->value("id");

        return DB::table("searches")->where([
            "word_id" => $wordId,
            "context_id" => $contextId,
            "count_pages" => $this->numOfPages,
            "items_per_page" => $this->itemsPerPage
        ])->first();
    }

    private function getServiceInstance(string $provider)
    {
        if ($provider === "github") {
            return new GitHubService(
                $this->word,
                env("GITHUB_API_ISSUES_ENDPOINT"),
                "",
                "",
                "",
                [
                    "Accept" => "application/vnd.github.text-match+json",
                    "Authorization" => "Bearer " . env("GITHUB_PERSONAL_ACCESS_TOKEN")
                ],
                $this->numOfPages,
                $this->itemsPerPage);
        }
        else if ($provider === "x") {
            return new XService(
                $this->word,
                "endpoint",
                "",
                "",
                "",
                [
                    "header" => "header"
                ],
                $this->numOfPages,
                $this->itemsPerPage);
        }
    }
    // end methods
}
