<?php
declare(strict_types=1);

namespace App\Repositories\Delivery;

use App\Models\Delivery;
use Illuminate\Support\Collection;
use Throwable;

class DeliveryRepository implements DeliveryRepositoryInterface
{
    /**
     * Создает новую доставку.
     *
     * @param Delivery $delivery
     * @return Delivery
     * @throws Throwable
     */
    function create(Delivery $delivery): Delivery
    {
        $delivery->saveOrFail();

        return $delivery;
    }

    /**
     * @param Delivery $delivery
     * @return bool
     * @throws Throwable
     */
    function update(Delivery $delivery): bool
    {
        return $delivery->saveOrFail();
    }

    /**
     * Получает доставки по идентификаторам заказов.
     *
     * @param array $orderIDs
     * @return Collection
     */
    function getByOrderIDs(array $orderIDs): Collection
    {
        return Delivery::whereIn('order_id', $orderIDs)->get();
    }

    /**
     * Получает доставку по идентификатору заказа.
     *
     * @param string $orderID
     * @return Delivery|null
     */
    function firstByOrderID(string $orderID): ?Delivery
    {
        return Delivery::where('order_id', $orderID)->first();
    }
}
