<?php

namespace App\Interfaces;

use Illuminate\Http\Client\Response;

interface SearchableInterface
{
    function search(): Response;

    function calcPopularityScore(): float;
}
