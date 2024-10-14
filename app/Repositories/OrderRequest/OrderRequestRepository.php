<?php
declare(strict_types=1);

namespace App\Repositories\OrderRequest;

use App\Models\OrderRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderRequestRepository implements OrderRequestRepositoryInterface
{

    /**
     * @param OrderRequest $orderRequest
     * @return OrderRequest
     * @throws Throwable
     */
    function create(OrderRequest $orderRequest): OrderRequest
    {
        $orderRequest->saveOrFail();

        return $orderRequest;
    }

    /**
     * @param string $vendorID
     * @param array $statuses
     * @return Collection
     */
    function getWithOrdersByStatuses(string $vendorID, array $statuses): Collection
    {
        return OrderRequest::with('order')
            ->whereIn('status', $statuses)
            ->where('vendor_id', $vendorID)
            ->get();
    }

    /**
     * @param string $vendorID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByStatusesPaginate(string $vendorID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return OrderRequest::with('order')
            ->whereIn('status', $statuses)
            ->where('vendor_id', $vendorID)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param string $orderID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByStatusesByOrderIDPaginate(string $orderID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return OrderRequest::with('order')
            ->whereIn('status', $statuses)
            ->where('order_id', $orderID)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param string $vendorID
     * @param array $statuses
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getWithOrdersByOrderStatusesByVendorIDPaginate(string $vendorID, array $statuses, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return OrderRequest::with('order')
            ->whereHas('order', function ($query) use ($statuses) {
                $query->whereIn('status', $statuses);
            })
            ->where('vendor_id', $vendorID)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param array $requestIDs
     * @return bool
     */
    function makeShown(array $requestIDs): bool
    {
        return boolval(OrderRequest::whereIn('id', $requestIDs)->update(['shown' => true]));
    }

    /**
     * @param string $vendorID
     * @return Collection
     */
    function getUnseenRequestCountToStatus(string $vendorID): Collection
    {
        return OrderRequest::select(["status", DB::raw('COUNT(*) as order_request_count')])->where('vendor_id', $vendorID)
            ->where('shown', false)->groupBy('status')->get();
    }

    /**
     * @param string $vendorID
     * @return Collection
     */
    function getUnseenRequestCountToOrderStatus(string $vendorID): Collection
    {
        return OrderRequest::select(["orders.status as order_status", DB::raw('COUNT(*) as order_request_count')])
            ->leftJoin('orders', 'orders.id', '=', 'order_requests.order_id')
            ->where('vendor_id', $vendorID)
            ->where('order_requests.shown', false)->groupBy('orders.status')->get();
    }

    /**
     * @param string $vendorID
     * @return Collection
     */
    function countUnseenOrders(string $vendorID): Collection
    {
        return OrderRequest::select(["orders.status", DB::raw('COUNT(*) as order_request_count')])->with('order')
            ->leftJoin('orders', 'orders.id', '=', 'order_requests.order_id')
            ->where('vendor_id', $vendorID)
            ->get();
    }

    /**
     * @param string $orderRequestID
     * @param string $vendorID
     * @return OrderRequest|null
     */
    function firstByVendorID(string $orderRequestID, string $vendorID): ?OrderRequest
    {
        return OrderRequest::where('id', $orderRequestID)
            ->where('vendor_id', $vendorID)
            ->first();
    }

    /**
     * @param string $orderRequestID
     * @param string $vendorID
     * @return OrderRequest|null
     */
    function firstByVendorIDWithOrder(string $orderRequestID, string $vendorID): ?OrderRequest
    {
        return OrderRequest::with('order')
            ->where('id', $orderRequestID)
            ->where('vendor_id', $vendorID)
            ->first();
    }


    /**
     * @param OrderRequest $orderRequest
     * @return bool
     * @throws Throwable
     */
    function update(OrderRequest $orderRequest): bool
    {
        return $orderRequest->saveOrFail();
    }

    /**
     * @param string $orderRequestID
     * @return OrderRequest|null
     */
    function firstByID(string $orderRequestID): ?OrderRequest
    {
        return OrderRequest::where('id', $orderRequestID)->first();
    }

    /**
     * @param string $vendorID
     * @param array $statuses
     * @return OrderRequest|null
     */
    function firstByStatuses(string $vendorID, array $statuses): ?OrderRequest
    {
        return OrderRequest::where('vendor_id', $vendorID)->whereIn('status', $statuses)->first();
    }

    /**
     * @param string $orderRequestID
     * @param string $orderID
     * @return OrderRequest|null
     */
    function firstByOrderID(string $orderRequestID, string $orderID): ?OrderRequest
    {
        return OrderRequest::where('id', $orderRequestID)->where('order_id', $orderID)->first();
    }

    /**
     * @param string $orderID
     * @return Collection
     */
    function getByOrderID(string $orderID): Collection
    {
        return OrderRequest::where('order_id', $orderID)->get();
    }

    /**
     * Получаем отклики на заказы по идентификатору заказа, исключая отклики со статусом 'cancelled'.
     *
     * @param string $orderID
     * @return Collection
     */
    public function getByOrderIDWithoutCancelled(string $orderID): Collection
    {
        return OrderRequest::where('order_id', $orderID)
            ->where('status', '!=', 'cancelled')
            ->get();
    }

    /**
     * @param string $orderRequestID
     * @return bool
     */
    function softDelete(string $orderRequestID): bool
    {
        return boolval(OrderRequest::where('id', $orderRequestID)->delete());
    }
}
