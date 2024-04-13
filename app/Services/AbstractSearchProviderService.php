<?php

namespace App\Services;

use App\Interfaces\SearchableInterface;
use Illuminate\Http\Client\Response;

abstract class AbstractSearchProviderService implements SearchableInterface
{
    protected string $word;
    protected string $authorizationToken;
    protected string $endpoint;
    protected string $username;
    protected string $contextName;
    protected array $headers;

    public abstract function search(): array;

    public abstract function calcPopularityScore(array $items): array;
}
