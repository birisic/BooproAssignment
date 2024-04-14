<?php

namespace App\Services;

use App\Models\Context;
use App\Models\Search;
use App\Models\SearchProvider;
use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class GitHubService extends AbstractSearchProviderService
{
    //constructors
    public function __construct($word, $endpoint, $username, $repository, $type, $headers, $numOfPages, $itemsPerPage)
    {
        // validate params
        $this->word = $word;
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->contextName = $repository;
        $this->type = $type;
        $this->headers = $headers;
        $this->numOfPages = $numOfPages;
        $this->itemsPerPage = $itemsPerPage;
    }
    //end constructors

    //methods
    public function search(): array
    {
        // limitations: 4000 repos, max 100 items per page
        $queryStringParams = [
            'q' => '"' . $this->word . ' rocks" OR "' . $this->word . ' sucks"',
            'per_page' => $this->itemsPerPage
        ];

        if ($this->username != null && $this->contextName != null){
            $queryStringParams['q'] = "$this->word repo:$this->username/$this->contextName";
        }

        $queryString = http_build_query($queryStringParams);

        $response = $this->makeRequest("$this->endpoint?$queryString");

        if (!isset($response["items"])) {
            throw new \Exception("No 'items' array retrieved from the response. Cannot search results.");
        }

        $allItems = $response["items"];
        $pageNum = 1;

        while ($pageNum <= $this->numOfPages) {
            $nextPageUrl = $this->getNextPageUrl($response);

            if (!isset($nextPageUrl)){
                break;
            }

            $pageNum = $this->getPageNumber($nextPageUrl);
            $response = $this->makeRequest($nextPageUrl);

            if (!isset($response["items"])) {
                throw new \Exception("No 'items' array retrieved from the response for page: $pageNum. Cannot search results.");
            }

            $allItems = array_merge($allItems, $response["items"]);
        }

        return $allItems;
    }

    public function calcPopularityScore(array $items): array
    {
        $arrOfStrings = [];
        foreach ($items as $issue) {
            if (!isset($issue["text_matches"])){
                throw new \Exception("Accept header doesn't include text-match option or there were no text matches for an issue.");
            }

            foreach ($issue["text_matches"] as $textMatch) {
                if (isset($textMatch["fragment"])){
                    $text = strtolower(str_replace(["\n", "\t", "", "\r\n", '.', ')', '-', ',', '\'', '"', '!', '?'], "", $textMatch["fragment"]));

                    if (preg_match("/\b(?:$this->word\s*(rocks|sucks))\b/i", $text)){
                        $arrOfStrings[] = $text;
                    }
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
//        return $arrOfWords;

        foreach ($arrOfWords as $words) {
            foreach ($words as $key=>$value) {
                if ($value === $this->word){
                    if (isset($words[$key + 1]) && $words[$key + 1] === "rocks"){
                        $counterPositive++;
                    }
                    else if (isset($words[$key + 1]) && $words[$key + 1] === "sucks"){
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
        try {
            $this->insertOrUpdateRecordsInDatabase($counterPositive, $counterNegative);

            return [
                "term" => $this->word,
                "positiveCount" => $counterPositive,
                "negativeCount" => $counterNegative,
                "total" => $counterTotal,
                "score" => round($score, 2)
            ];
        }
        catch (\Exception $e){
            throw new \Exception("Database exception - " . $e->getMessage());
        }
    }

    private function insertOrUpdateRecordsInDatabase(int $positiveCount, int $negativeCount): void
    {
        if ((!isset($positiveCount) || $positiveCount < 0) || (!isset($negativeCount) || $negativeCount < 0)){
            throw new \Exception("Positive or negative results are of invalid value. Positive: $positiveCount; Negative: $negativeCount");
        }

        try {
            DB::beginTransaction();
            $arrIds = $this->firstOrCreateWordAndContextIds();

            $wordId = $arrIds["wordId"];
            $contextId = $arrIds["contextId"];

            $existingRecord = $this->findExistingSearchRecord($wordId, $contextId);

            if (!$existingRecord) {
                $this->insertNewSearchRecord($wordId, $contextId, $positiveCount, $negativeCount);
                DB::commit();
                return;
            }

            if ($existingRecord->count_positive !== $positiveCount || $existingRecord->count_negative !== $negativeCount) {
                $columnsToUpdate = [
                    "count_positive" => $positiveCount,
                    "count_negative" => $negativeCount,
                    "updated_at" => now(),
                ];
            }
            else {
                $columnsToUpdate = [
                    "updated_at" => now(),
                ];
            }

            $this->updateExistingSearchRecord($wordId, $contextId, $columnsToUpdate);

            DB::commit();
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
    //end methods
    private function firstOrCreateWordAndContextIds(): array
    {
        $arrIds = [];
        $providerId = SearchProvider::getProviderId("GitHub");

        if (!isset($providerId)) {
            throw new \Exception("Provider ID not found.");
        }

        $word = Word::firstOrCreate(["name" => $this->word]);
        $wordId = $word->id;

        if (!isset($wordId)){
            throw new \Exception("Could not insert or update a search record. WordId is missing. WordId: $wordId");
        }
        $arrIds["wordId"] = $wordId;

        $context = Context::firstOrCreate([
            "name" => $this->contextName !== '' ? $this->contextName : null,
            "owner_username" => $this->username !== '' ? $this->username : null,
            "type" => $this->type !== '' ? $this->type : null,
            "provider_id" => $providerId,
        ]);
        $contextId = $context->id;

        if (!isset($contextId)){
            throw new \Exception("Could not insert or update a search record. ContextId is missing. ContextId: $contextId");
        }
        $arrIds["contextId"] = $contextId;

        return $arrIds;
    }

    private function findExistingSearchRecord(int $wordId, int $contextId): ?object
    {
        return DB::table("searches")->where([
            "word_id" => $wordId,
            "context_id" => $contextId,
            "count_pages" => $this->numOfPages,
            "items_per_page" => $this->itemsPerPage
        ])->first();
    }

    private function insertNewSearchRecord(int $wordId, int $contextId, int $positiveCount, int $negativeCount): void
    {
        DB::table("searches")->insert([
            "word_id" => $wordId,
            "context_id" => $contextId,
            "count_pages" => $this->numOfPages,
            "items_per_page" => $this->itemsPerPage,
            "count_positive" => $positiveCount,
            "count_negative" => $negativeCount,
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }

    private function updateExistingSearchRecord(int $wordId, int $contextId, array $columnsToUpdate): void
    {
        DB::table("searches")->where([
            "word_id" => $wordId,
            "context_id" => $contextId,
            "count_pages" => $this->numOfPages,
            "items_per_page" => $this->itemsPerPage
        ])->update($columnsToUpdate);
    }

    private function makeRequest(string $url): Response
    {
        return Http::withHeaders($this->headers)->get($url);
    }

    private function getPageNumber(string $url): int
    {
        $queryString = parse_url($url, PHP_URL_QUERY);
        parse_str($queryString, $queryParams);
        return $queryParams['page'] ?? 1;
    }

    private function getNextPageUrl(Response $response): ?string
    {
        if ($response->hasHeader('Link')) {
            $linkHeader = $response->header('Link');

            preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches);
            if (isset($matches[1])) {
                return $matches[1]; //next page url
            }
        }

        return null;
    }
}
