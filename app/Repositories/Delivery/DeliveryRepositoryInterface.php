<?php
declare(strict_types=1);

namespace App\Repositories\Delivery;

use App\Models\Delivery;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Управляет delivery сущностью в БД.
 */
interface DeliveryRepositoryInterface
{

    /**
     * Создает доставку.
     *
     * @param Delivery $delivery
     * @return Delivery
     * @throws Throwable
     */
    function create(Delivery $delivery): Delivery;

    /**
     * Получаем доставку по идентификатору заказа.
     *
     * @param string $orderID
     * @return Delivery|null
     */
    function firstByOrderID(string $orderID): ?Delivery;

    /**
     * Обновляем модель.
     *
     * @param Delivery $delivery
     * @return bool
     */
    function update(Delivery $delivery): bool;

    /**
     * Получаем доставки по идентификаторам заказов.
     *
     * @param array $orderIDs
     * @return Collection
     */
    function getByOrderIDs(array $orderIDs): Collection;
}
