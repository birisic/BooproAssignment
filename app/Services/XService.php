<?php

namespace App\Services;

use App\Interfaces\SearchableInterface;

class XService extends AbstractSearchProviderService implements SearchableInterface
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
        return [];
    }

    public function calcPopularityScore(): array
    {
        return [];
    }
}
