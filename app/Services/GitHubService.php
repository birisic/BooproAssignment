<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

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
            throw new \Exception("Response was not set.");
        }

        return $response;
    }

    public function calcPopularityScore(): float
    {
        return 0;
    }
}
