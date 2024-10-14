<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Delivery\DeliveryRepositoryInterface;
use App\Services\DeliveryService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(DeliveryService::class, function ($app) {
            return new DeliveryService($app->get(DeliveryRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [DeliveryService::class];
    }
}
