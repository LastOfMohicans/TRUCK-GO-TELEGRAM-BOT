<?php
declare(strict_types=1);

namespace App\Repositories\OrderRequestHistory;

use App\Models\OrderRequestHistory;
use Throwable;

class OrderRequestHistoryRepository implements OrderRequestHistoryRepositoryInterface
{
    /**
     * @param OrderRequestHistory $orderRequestHistory
     * @return OrderRequestHistory
     * @throws Throwable
     */
    function create(OrderRequestHistory $orderRequestHistory): OrderRequestHistory
    {
        $orderRequestHistory->saveOrFail();

        return $orderRequestHistory;
    }
}
