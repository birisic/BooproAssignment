<?php

namespace App\Interfaces;

interface SearchableInterface
{
    public function search(): array;

    public function calcPopularityScore(): array;
}
