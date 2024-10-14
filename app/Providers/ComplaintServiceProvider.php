<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Services\ComplaintService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ComplaintServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(ComplaintService::class, function ($app) {
            return new ComplaintService($app->get(ComplaintRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [ComplaintService::class];
    }
}
