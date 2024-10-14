<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\MaterialQuestionAnswer\MaterialQuestionAnswerRepositoryInterface;
use App\Services\MaterialQuestionAnswersService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MaterialQuestionAnswersServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(MaterialQuestionAnswersService::class, function ($app) {
            return new MaterialQuestionAnswersService($app->get(MaterialQuestionAnswerRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [MaterialQuestionAnswersService::class];
    }
}
