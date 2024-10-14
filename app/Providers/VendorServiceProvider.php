<?php
declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GeocodingInterface;
use App\Contracts\INNInterface;
use App\Repositories\Client\ClientRepositoryInterface;
use App\Repositories\Vendor\VendorRepositoryInterface;
use App\Services\VendorService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class VendorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(
            VendorService::class,
            function ($app) {
                return new VendorService(
                    $app->get(GeocodingInterface::class),
                    $app->get(INNInterface::class),
                    $app->get(VendorRepositoryInterface::class),
                    $app->get(ClientRepositoryInterface::class),
                );
            }
        );
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [VendorService::class];
    }
}
