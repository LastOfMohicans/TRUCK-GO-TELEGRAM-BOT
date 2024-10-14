<?php

namespace App\Jobs;

use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача, которая отвечает за отмену заказов, срок действия которых истек.
 */
class CancelExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected OrderService $orderService;

    /**
     * Create a new job instance.
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->orderService->getExpiredOrders()->each(function ($order) {
            CancelOrderJob::dispatch($order);
        });
    }
}
