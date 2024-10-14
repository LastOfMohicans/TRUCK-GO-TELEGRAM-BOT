<?php
declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Delivery\DeliveryRepositoryInterface;
use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderHistory\OrderHistoryRepositoryInterface;
use App\Repositories\OrderQuestionAnswer\OrderQuestionAnswerRepositoryInterface;
use App\Repositories\OrderRequest\OrderRequestRepositoryInterface;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepositoryInterface;
use App\Services\OrderRequestService;
use App\Services\OrderService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderService::class, function (Application $app) {
            return new OrderService(
                $app->get(MaterialQuestionRepositoryInterface::class),
                $app->get(OrderRepositoryInterface::class),
                $app->get(OrderRequestRepositoryInterface::class),
                $app->get(OrderQuestionAnswerRepositoryInterface::class),
                $app->get(OrderHistoryRepositoryInterface::class),
                $app->get(OrderRequestHistoryRepositoryInterface::class),
                $app->get(DeliveryRepositoryInterface::class),
                $app->get(OrderRequestService::class),
            );
        });
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [OrderService::class];
    }
}
