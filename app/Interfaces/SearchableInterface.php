<?php

namespace App\Interfaces;

use Illuminate\Http\Client\Response;

interface SearchableInterface
{
    function search(): array;

    function calcPopularityScore(array $items): array;
}
