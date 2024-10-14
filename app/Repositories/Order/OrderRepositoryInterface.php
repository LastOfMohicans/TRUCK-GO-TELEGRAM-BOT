<?php
declare(strict_types=1);

namespace App\Repositories\Order;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Throwable;

/**
 * Управляет order сущностью в БД.
 */
interface OrderRepositoryInterface
{

    /**
     * Удаляем заказ, устанавливая ему deleted_at.
     *
     * @param string $orderID
     * @param string $clientID
     * @return bool
     */
    function softDelete(string $orderID, string $clientID): bool;

    /**
     * Получаем заказ по идентификатору.
     *
     * @param string $orderID
     * @return Order|null
     */
    function first(string $orderID): ?Order;

    /**
     * Получаем заказ по идентификатору и идентификатору клиента.
     *
     * @param string $orderID
     * @param string $clientID
     * @return Order|null
     */
    function firstByClientID(string $orderID, string $clientID): ?Order;

    /**
     * Получаем все заказы с пагинацией.
     *
     * @param string $clientID
     * @param int $page
     * @param int $perPage
     * @return array|LengthAwarePaginator
     */
    function getPaginate(string $clientID, int $page, int $perPage = 5): array|LengthAwarePaginator;

    /**
     * Получаем заказы по статусу с пагинацией.
     *
     * @param string $clientID
     * @param int $page
     * @param array $statuses
     * @param int $perPage
     * @return array|LengthAwarePaginator
     */
    function getByStatusesPaginate(string $clientID, int $page, array $statuses, int $perPage = 5): array|LengthAwarePaginator;

    /**
     * @param Order $order
     * @return Order
     * @throws Throwable
     */
    function create(Order $order): Order;

    /**
     * Получаем все заказы с заданным статусом.
     * Выводим количество по параметру $page.
     *
     * @param string $userID
     * @param int $page
     * @param array $statuses
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatuses(string $userID, int $page, array $statuses, int $perPage = 1): LengthAwarePaginator;

    /**
     * Получаем айди заказов и дистанцию до их места доставки(считается напрямую), ограниченный радиусом.
     * Так же получаем координаты каждого заказа.
     * Возвращает пустую коллекцию, если подобающих заказов не найдено.
     *
     * @param string $latitude   Широта склада.
     * @param string $longitude  Долгота склада.
     * @param int $radius        Приемлемый радиус. Если заказ доставлять дальше чем радиус, то он не попадает в
     *                           выборку.
     * @param array $materialIDs Идентификаторы материалов которым должны соответствовать заказы.
     * @return Collection
     */
    function getActiveIDsInRadius(string $latitude, string $longitude, int $radius, array $materialIDs): Collection;

    /**
     * Получаем заказы по идентификатору.
     *
     * @param array $orderIDs
     * @return Collection
     */
    function get(array $orderIDs): Collection;

    /**
     * Обновляем заказ.
     *
     * @param Order $order
     * @return bool
     * @throws Throwable
     */
    function update(Order $order): bool;

    /**
     * Возвращает все заказы с указанными статусами до определенной даты включительно.
     *
     * @param array $orderStatuses
     * @param string $date
     * @return LazyCollection
     */
    public function getUpToDateByLazyChunk(array $orderStatuses, string $date): LazyCollection;

    /**
     * Получаем количество заказов с определенным статусом.
     *
     * @param string $clientID
     * @param array $statuses
     * @return int
     */
    public function countByStatuses(string $clientID, array $statuses): int;
}
