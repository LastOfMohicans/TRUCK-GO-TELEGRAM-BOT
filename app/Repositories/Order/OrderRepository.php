<?php
declare(strict_types=1);

namespace App\Repositories\Order;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Throwable;

class OrderRepository implements OrderRepositoryInterface
{

    /**
     * @param string $orderID
     * @param string $clientID
     * @return bool
     */
    public function softDelete(string $orderID, string $clientID): bool
    {
        return boolval(
            Order::where(
                [
                    'id' => $orderID,
                    'client_id' => $clientID,
                ]
            )->delete()
        );
    }

    /**
     * @param string $clientID
     * @param int $page
     * @param int $perPage
     * @return array|LengthAwarePaginator
     */
    public function getPaginate(string $clientID, int $page, int $perPage = 5): array|LengthAwarePaginator
    {
        return Order::where('client_id', $clientID)
            ->paginate(perPage: $perPage, page: $page);
    }

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
    public function getByStatuses(string $userID, int $page, array $statuses, int $perPage = 1): LengthAwarePaginator
    {
        return Order::where('client_id', $userID)
            ->whereIn('status', $statuses)
            ->paginate($page);
    }

    /**
     * @param string $clientID
     * @param int $page
     * @param array $statuses
     * @param int $perPage
     * @return array|LengthAwarePaginator
     */
    public function getByStatusesPaginate(string $clientID, int $page, array $statuses, int $perPage = 5): array|LengthAwarePaginator
    {
        return Order::where('client_id', $clientID)
            ->whereIn('status', $statuses)
            ->where('is_activated', true)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param Order $order
     * @return Order
     * @throws Throwable
     */
    public function create(Order $order): Order
    {
        $order->saveOrFail();

        return $order;
    }


    /**
     * @param string $latitude
     * @param string $longitude
     * @param int $radius
     * @param array $materialIDs
     * @return Collection
     */
    public function getActiveIDsInRadius(string $latitude, string $longitude, int $radius, array $materialIDs): Collection
    {
        $subquery = DB::table('orders as o')
            ->leftJoin('deliveries as d', 'o.id', '=', 'd.order_id')
            ->leftJoin('order_requests as or', 'o.id', '=', 'or.order_id') // джойним чтобы исключить заказы, которые уже добавлены
            ->select(
                DB::raw(
                    "
        COALESCE((6371 * acos(
            cos(radians($latitude)) *
            cos(radians(d.latitude)) *
            cos(radians(d.longitude) - radians($longitude)) +
            sin(radians($latitude)) *
            sin(radians(d.latitude))
        )), 1) AS distance,
        o.id,
        d.latitude,
        d.longitude
    "
                )
            )
            ->where('or.order_id', null)
            ->where('o.is_activated', true)
            ->whereIn('material_id', $materialIDs)
            ->whereNull('o.deleted_at');

        return DB::table(DB::raw("({$subquery->toSql()}) as subquery"))
            ->mergeBindings($subquery) // Merge bindings for the subquery
            ->select('subquery.distance', 'subquery.id as order_id', 'subquery.latitude', 'subquery.longitude')
            ->where('subquery.distance', '<', $radius)
            ->get()
            ->select(['order_id', 'distance', 'latitude', 'longitude']);
    }

    /**
     * @param array $orderIDs
     * @return Collection
     */
    public function get(array $orderIDs): Collection
    {
        return Order::whereIn('id', $orderIDs)->get();
    }

    public function first(string $orderID): ?Order
    {
        return Order::where('id', $orderID)->first();
    }

    public function firstByClientID(string $orderID, string $clientID): ?Order
    {
        return Order::where('id', $orderID)
            ->where('client_id', $clientID)
            ->first();
    }

    /**
     * Обновляем заказ.
     *
     * @param Order $order
     * @return bool
     * @throws Throwable
     */
    public function update(Order $order): bool
    {
        return $order->saveOrFail();
    }

    /**
     * Возвращает все заказы с указанными статусами до определенной даты включительно.
     *
     * @param array $orderStatuses
     * @param string $date
     * @return LazyCollection
     */
    public function getUpToDateByLazyChunk(array $orderStatuses, string $date): LazyCollection
    {
        return Order::with('delivery')
            ->whereIn('status', $orderStatuses)
            ->whereHas('delivery', function ($query) use ($date) {
                $query->whereDate('wanted_delivery_window_end', '<=', $date);
            })
            ->select('orders.*')->lazy();
    }

    /**
     * Получаем количество заказов с определенным статусом.
     *
     * @param string $clientID
     * @param array $statuses
     * @return int
     */
    public function countByStatuses(string $clientID, array $statuses): int
    {
        return Order::where('client_id', $clientID)
            ->whereIn('status', $statuses)
            ->where('is_activated', true)
            ->count();
    }
}
