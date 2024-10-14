<?php
declare(strict_types=1);

namespace App\Repositories\OrderHistory;


use App\Models\OrderHistory;
use Illuminate\Support\Collection;
use Throwable;

class OrderHistoryRepository implements OrderHistoryRepositoryInterface
{
    /**
     * @param OrderHistory $orderHistory
     * @return OrderHistory
     * @throws Throwable
     */
    public function create(OrderHistory $orderHistory): OrderHistory
    {
        $orderHistory->saveOrFail();

        return $orderHistory;
    }
}
