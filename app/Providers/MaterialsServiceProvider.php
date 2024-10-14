<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use App\Services\MaterialService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MaterialsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(MaterialService::class, function ($app) {
            return new MaterialService($app->get(MaterialQuestionRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [MaterialService::class];
    }
}
