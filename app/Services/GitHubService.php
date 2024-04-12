<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class GitHubService extends AbstractSearchProviderService
{
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

    public function search(): Response
    {
        $queryStringParams = [
            'q' => "$this->word" //maybe add qualifier for issues only
        ];

        if ($this->username != null && $this->contextName != null){
            $queryStringParams['q'] = "$this->word repo:$this->username/$this->contextName";
        }

        $queryString = http_build_query($queryStringParams);

        $response = Http::withHeaders($this->headers)->get("$this->endpoint?$queryString");

        if (!isset($response)){
            throw new \Exception("No response was received from GitHub.");
        }

        if (!isset($response["items"])) {
            throw new \Exception("No 'items' array retrieved from the response. Cannot search results.");
        }

        return $response;
    }

    public function calcPopularityScore(array $items): array
    {
        $arrOfStrings = [];
        foreach ($items as $issue) {
            if (!$issue["text_matches"]){
                throw new \Exception("Accept header doesn't include text-match option or there were no text matches for an issue.");
            }

            foreach ($issue["text_matches"] as $textMatch) {
                $text = strtolower(str_replace(["\n", "\t", ""], "", $textMatch["fragment"]));

                if (preg_match("/\b(?:$this->word\s*(rocks|sucks))\b/i", $text)){
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
                if ($value === $this->word){
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

        // before modifying, check if there is a record in the database for this word and in the given context.
        // if there is, then check if it was updated within the last hour. If that's the case, don't update anything in the database.
        // otherwise, update with fresh data about its results.

        // insert/update in the database
        $hasModified = $this->upsertInDatabase($this->word, $this->contextName, "GitHub");
        if (!$hasModified){
//            Log::error("Couldn't insert or update records for the database for the given word.");
            throw new \Exception("Couldn't insert or update records in the database for the given word.");
        }

        return [
            "term" => $this->word,
            "positiveCount" => $counterPositive,
            "negativeCount" => $counterNegative,
            "total" => $counterTotal,
            "score" => round($score, 2)
        ];
    }

    private function upsertInDatabase($word, $contextName, $providerName): bool
    {

        return false;
    }
}
