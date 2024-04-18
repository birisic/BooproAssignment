<?php

namespace App\Providers;

use App\Interfaces\SearchableInterface;
use App\Services\AbstractSearchProviderService;
use App\Services\GitHubService;
use App\Services\XService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(SearchableInterface::class, GitHubService::class);
    }
}
