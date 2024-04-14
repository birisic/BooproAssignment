<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class GitHubService extends AbstractSearchProviderService
{
    //constructors
    public function __construct($word, $endpoint, $authToken, $username, $repository, $headers)
    {
        // validate params
        $this->word = $word;
        $this->authorizationToken = $authToken;
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->contextName = $repository;
        $this->headers = $headers;
    }
    //end constructors

    //methods
    public function search(): array
    {
        $numOfPages = 2; //number of pages to load from GitHub api ($numOfPages * per_page ~= $numOfResults)

        // limitations: 4000 repos, max 100 items per page
        $queryStringParams = [
            'q' => '"' . $this->word . ' rocks" OR "' . $this->word . ' sucks"',
            'per_page' => 100
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

        while ($pageNum <= $numOfPages) {
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
//        $hasModified = $this->upsertInDatabase($this->word, $this->contextName, "GitHub");
//        if (!$hasModified){
////            Log::error("Couldn't insert or update records for the database for the given word.");
//            throw new \Exception("Couldn't insert or update records in the database for the given word.");
//        }

        return [
            "term" => $this->word,
            "positiveCount" => $counterPositive,
            "negativeCount" => $counterNegative,
            "total" => $counterTotal,
            "score" => round($score, 2)
        ];
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



    private function upsertInDatabase($word, $contextName, $providerName): bool
    {

        return false;
    }
    //end methods
}
