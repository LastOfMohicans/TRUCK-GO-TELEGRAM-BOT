<?php
declare(strict_types=1);

namespace App\Repositories\OrderRequest;

use App\Models\OrderRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Управляет order_request сущностью в БД.
 */
interface OrderRequestRepositoryInterface
{
    /**
     * Создание сущности отклика на заказ.
     *
     * @param OrderRequest $orderRequest
     * @return OrderRequest
     * @throws Throwable
     */
    function create(OrderRequest $orderRequest): OrderRequest;


    /**
     * Получаем отклики на заказы с заказами по статусам.
     *
     * @param string $vendorID
     * @param array $statuses
     * @return Collection
     */
    function getWithOrdersByStatuses(string $vendorID, array $statuses): Collection;

    /**
     * Получаем отклики на заказы с заказами по статусам откликов и пагинацией.
     *
     * @param string $vendorID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByStatusesPaginate(string $vendorID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем отклики на заказы с заказами по статусам откликов и пагинацией.
     *
     * @param string $orderID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByStatusesByOrderIDPaginate(string $orderID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем отклики на заказы с заказами по статусам ЗАКАЗОВ и пагинацией.
     *
     * @param string $vendorID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByOrderStatusesByVendorIDPaginate(string $vendorID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем отклики на заказы с заказами по статусам ЗАКАЗОВ и пагинацией.
     *
     * @param array $requestIDs
     * @return bool
     */
    function makeShown(array $requestIDs): bool;

    /**
     * Получаем количество непоказанных откликов по статусам.
     *
     * @param string $vendorID
     * @return Collection
     */
    function getUnseenRequestCountToStatus(string $vendorID): Collection;

    /**
     * Получаем количество непоказанных откликов по статусам заказа.
     *
     * @param string $vendorID
     * @return Collection
     */
    function getUnseenRequestCountToOrderStatus(string $vendorID): Collection;

    /**
     * Получаем отклик.
     *
     * @param string $orderRequestID
     * @param string $orderID
     * @return OrderRequest|null
     */
    function firstByOrderID(string $orderRequestID, string $orderID): ?OrderRequest;

    /**
     * Получаем отклик.
     *
     * @param string $orderRequestID
     * @param string $vendorID
     * @return OrderRequest|null
     */
    function firstByVendorIDWithOrder(string $orderRequestID, string $vendorID): ?OrderRequest;

    /**
     * Получаем отклик для поставщика по статусу.
     *
     * @param string $vendorID
     * @param array $statuses
     * @return OrderRequest|null
     */
    function firstByStatuses(string $vendorID, array $statuses): ?OrderRequest;

    /**
     * Получаем отклик по идентификатору.
     *
     * @param string $orderRequestID
     * @return OrderRequest|null
     */
    function firstByID(string $orderRequestID): ?OrderRequest;

    /**
     * Обновляем отклик.
     *
     * @param OrderRequest $orderRequest
     * @return bool
     * @throws Throwable
     */
    function update(OrderRequest $orderRequest): bool;

    /**
     * Получаем отклики на заказы по идентификатору заказа.
     *
     * @param string $orderID
     * @return Collection
     */
    function getByOrderID(string $orderID): Collection;

    /**
     * Получаем отклики на заказы по идентификатору заказа, исключая отклики со статусом 'cancelled'.
     *
     * @param string $orderID
     * @return Collection
     */
    public function getByOrderIDWithoutCancelled(string $orderID): Collection;

    /**
     * Удаляем отклик на заказ, устанавливая ему deleted_at.
     *
     * @param string $orderRequestID
     * @return bool
     */
    function softDelete(string $orderRequestID): bool;
}
