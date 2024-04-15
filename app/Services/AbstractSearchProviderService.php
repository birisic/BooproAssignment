<?php

namespace App\Services;

use App\Interfaces\SearchableInterface;
use Illuminate\Http\Client\Response;

abstract class AbstractSearchProviderService
{
    protected string $word;
    protected string $endpoint;
    protected string $username;
    protected string $contextName;
    protected string $type;
    protected array $headers;
    protected int $numOfPages;
    protected int $itemsPerPage;

    public abstract function search(): array;

    public abstract function calcPopularityScore(array $items): array;
}
