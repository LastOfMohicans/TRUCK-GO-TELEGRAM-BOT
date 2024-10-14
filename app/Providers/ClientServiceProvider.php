<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Client\ClientRepositoryInterface;
use App\Services\ClientService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(ClientService::class, function ($app) {
            return new ClientService($app->get(ClientRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [ClientService::class];
    }
}
