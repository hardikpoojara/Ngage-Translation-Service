<?php

namespace App\Providers;

use App\Repositories\ContentTranslationRepository;
use App\Repositories\Contracts\ContentTranslationRepositoryInterface;
use App\Services\OpenAI\OpenAIConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ContentTranslationRepositoryInterface::class,
            ContentTranslationRepository::class
        );

        $this->app->singleton(OpenAIConnector::class, function () {
            return new OpenAIConnector();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
