<?php
declare(strict_types=1);

namespace App\Repositories\OrderRequestHistory;

use App\Models\OrderRequestHistory;
use Throwable;

/**
 * Управляет order_request_history сущностью в БД.
 */
interface OrderRequestHistoryRepositoryInterface
{
    /**
     * Создание сущности историй отклика на заказ.
     *
     * @param OrderRequestHistory $orderRequestHistory
     * @return OrderRequestHistory
     * @throws Throwable
     */
    function create(OrderRequestHistory $orderRequestHistory): OrderRequestHistory;
}

