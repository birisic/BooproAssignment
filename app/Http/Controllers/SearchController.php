<?php

namespace App\Http\Controllers;

use App\Enums\SearchProviderEnum;
use App\Interfaces\SearchableInterface;
use App\Models\Context;
use App\Models\SearchProvider;
use App\Models\Word;
use App\Services\GitHubService;
use App\Services\XService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    // fields
    private string $word;
    private int $numOfPages = 2;
    private int $itemsPerPage = 100;
    private SearchableInterface $serviceInstance;
    // end fields

    // methods
    /**
     * @OA\Info(
     *      title="Word popularity score API",
     *      version="1.0.0",
     *      description="Laravel 11 API that retrieves the popularity score for a given word on a specified platform."
     *  )
     * @OA\Get(
     *     path="/api/score/{word}/{platform}",
     *     summary="Get Word Popularity",
     *     description="Endpoint to retrieve the popularity score for a word on a specified platform.",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="word",
     *         in="path",
     *         required=true,
     *         description="The word for which to retrieve the popularity score.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="platform",
     *         in="path",
     *         allowEmptyValue=true,
     *         required=false,
     *         description="The platform from which to retrieve the popularity score (optional).",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success: Popularity score retrieved.",
     *         @OA\JsonContent(
     *             @OA\Property(property="term", type="string", example="php"),
     *             @OA\Property(property="countPositive", type="integer", example=3),
     *             @OA\Property(property="countNegative", type="integer", example=7),
     *             @OA\Property(property="countTotal", type="integer", example=10),
     *             @OA\Property(property="score", type="integer", example=0.3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error: An unexpected error occurred."
     *     )
     * )
     */
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

            // make service instance
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
        return $this->serviceInstance->calcPopularityScore();
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

        if ($updatedAt->greaterThanOrEqualTo($threshold)) {
            $arrOutput = $this->calcPopularityScore($searchRecord);
        }
        else {
            $arrOutput = $this->searchAndModifyDatabase();
        }

        return $arrOutput;
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
                $this->itemsPerPage
            );
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
                $this->itemsPerPage
            );
        }

        return null;
    }
    // end methods
}
