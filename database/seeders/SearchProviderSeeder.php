<?php

namespace Database\Seeders;

use App\Enums\SearchProviderEnum;
use App\Models\SearchProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SearchProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SearchProviderEnum::cases() as $providerName) {
            SearchProvider::create([
                "name" => $providerName->value,
            ]);
        }
    }
}
