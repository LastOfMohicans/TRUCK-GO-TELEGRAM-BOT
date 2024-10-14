<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Repositories\Delivery\DeliveryRepositoryInterface;
use Illuminate\Support\Collection;

class DeliveryService
{
    protected DeliveryRepositoryInterface $deliveryRepository;

    public function __construct(DeliveryRepositoryInterface $deliveryRepository)
    {
        $this->deliveryRepository = $deliveryRepository;
    }


    /**
     * Получаем доставку.
     *
     * @param string $orderID
     * @return Delivery|null
     */
    function firstDeliveryByOrderID(string $orderID): ?Delivery
    {
        return $this->deliveryRepository->firstByOrderID($orderID);
    }

    /**
     * Сохраняем изменения в модели.
     *
     * @param Delivery $delivery
     * @return bool
     */
    function updateDelivery(Delivery $delivery): bool
    {
        return $this->deliveryRepository->update($delivery);
    }

    /**
     * Получаем мапу [order_id] = delivery.
     *
     * @param array $orderIDs
     * @return Collection
     */
    function getDeliveryByOrderIDByOrderIDs(array $orderIDs): Collection
    {
        $deliveries = $this->deliveryRepository->getByOrderIDs($orderIDs);

        return $deliveries->mapWithKeys(function ($item) {
            /** @var Delivery $item */
            return [$item->order_id => $item];
        });
    }
}
