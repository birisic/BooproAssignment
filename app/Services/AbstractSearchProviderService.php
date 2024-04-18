<?php

namespace App\Services;

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


    public function getContextName(): string { return $this->contextName; }
    public function getUsername(): string { return $this->contextName; }
    public function getType(): string { return $this->contextName; }


    public abstract function search(): array;

    public abstract function calcPopularityScore(array $items): array;
}
