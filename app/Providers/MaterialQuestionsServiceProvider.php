<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use App\Services\MaterialQuestionsService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MaterialQuestionsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {

        $this->app->singleton(MaterialQuestionsService::class, function ($app) {
            return new MaterialQuestionsService($app->get(MaterialQuestionRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [MaterialQuestionsService::class];
    }
}
