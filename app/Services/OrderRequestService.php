<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderHistoryChanger;
use App\Exceptions\FailedCancelDiscountRequestException;
use App\Exceptions\FailedCancelOrderRequestException;
use App\Exceptions\FailedMakeDiscountForOrderException;
use App\Exceptions\FailedMakeOfferForOrderException;
use App\Exceptions\InvalidOrderRequestStatusException;
use App\Models\OrderRequest;
use App\Models\OrderRequestHistory;
use App\Repositories\OrderRequest\OrderRequestRepositoryInterface;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepositoryInterface;
use App\Repositories\StorageMaterial\StorageMaterialRepositoryInterface;
use App\StateMachines\OrderRequestStatusStateMachine;
use App\StateMachines\OrderStatusStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderRequestService
{

    protected OrderRequestRepositoryInterface $orderRequestRepository;
    protected OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository;
    protected StorageMaterialRepositoryInterface $storageMaterialRepository;

    /**
     * @param OrderRequestRepositoryInterface $orderRequestRepository
     * @param OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository
     * @param StorageMaterialRepositoryInterface $storageMaterialRepository
     */
    public function __construct(
        OrderRequestRepositoryInterface    $orderRequestRepository,
        OrderRequestHistoryRepositoryInterface  $orderRequestHistoryRepository,
        StorageMaterialRepositoryInterface $storageMaterialRepository,
    )
    {
        $this->orderRequestRepository = $orderRequestRepository;
        $this->orderRequestHistoryRepository = $orderRequestHistoryRepository;
        $this->storageMaterialRepository = $storageMaterialRepository;
    }

    /**
     * Получаем все отклики с заказами, на которые можно откликнуться.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getCreatedRequestsWithOrders(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        $requests = $this->orderRequestRepository->getWithOrdersByStatusesPaginate(
            $vendorID,
            [OrderRequestStatusStateMachine::CREATED],
            $page,
            $perPage
        );

        $requestIDs = [];
        foreach ($requests as $request) {
            $requestIDs[] = $request->id;
        }

        $this->orderRequestRepository->makeShown($requestIDs);

        return $requests;
    }

    /**
     * Получаем новый отклик на который можно откликнуться. Где отклик со статусом created.
     *
     * @param string $vendorID
     * @return OrderRequest|null
     */
    public function firstCreatedOrderRequest(string $vendorID): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByStatuses(
            $vendorID,
            [OrderRequestStatusStateMachine::CREATED]
        );

        if (is_null($orderRequest)) {
            return null;
        }

        if (!$orderRequest->shown) {
            $this->orderRequestRepository->makeShown([$orderRequest->id]);
        }

        return $orderRequest;
    }

    /**
     * Получаем отклики на которые хотят скидку. Где отклик со статусом client_want_discount.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getClientWantDiscountOrderRequestsPaginate(string $vendorID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        $orderRequestPaginator = $this->orderRequestRepository->getWithOrdersByStatusesPaginate(
            $vendorID,
            [OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT],
            $page,
            $perPage
        );

        $this->makeShownOrderRequests($orderRequestPaginator);

        return $orderRequestPaginator;

    }


    /**
     * Получаем все запросы с заказами в статусе loading.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRequestsWithLoadingOrders(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        $resp = $this->orderRequestRepository->getWithOrdersByOrderStatusesByVendorIDPaginate(
            $vendorID,
            [OrderStatusStateMachine::LOADING],
            $page,
            $perPage
        );

        $this->makeShownOrderRequests($resp);

        return $resp;
    }

    /**
     * Получаем все запросы с заказами в статусе on_the_way.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRequestsWithOnTheWayOrders(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        $resp = $this->orderRequestRepository->getWithOrdersByOrderStatusesByVendorIDPaginate(
            $vendorID,
            [OrderStatusStateMachine::ON_THE_WAY],
            $page,
            $perPage
        );

        $this->makeShownOrderRequests($resp);

        return $resp;
    }

    /**
     * Получаем запросы с заказами в статусе waiting_documents.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWaitingDocumentsRequestsWithOrders(string $vendorID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        $orderRequestPaginator = $this->orderRequestRepository->getWithOrdersByStatusesPaginate(
            $vendorID,
            [OrderRequestStatusStateMachine::WAITING_DOCUMENTS],
            $page,
            $perPage
        );

        $this->makeShownOrderRequests($orderRequestPaginator);

        return $orderRequestPaginator;
    }

    /**
     * Получаем запросы с заказами. Где отклик на заказ в статусе в котором его может принять клиент.
     *
     * @param string $orderID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRequestsWhichClientCanAcceptWithOrdersByOrderIDPaginate(string $orderID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        return $this->orderRequestRepository->getWithOrdersByStatusesByOrderIDPaginate(
            $orderID,
            OrderRequestStatusStateMachine::getStatusesWhichClientCanAccept(),
            $page,
            $perPage
        );
    }

    /**
     * Получаем массив непоказанных откликов сгруппированных по статусу.
     * Массив вида [отклик_статус => количество]
     *
     * @param string $vendorID
     * @return array
     */
    public function getUnseenRequestCountToStatus(string $vendorID): array
    {
        $unseenRequestsGroupByStatuses = $this->orderRequestRepository->getUnseenRequestCountToStatus($vendorID);

        if ($unseenRequestsGroupByStatuses->isEmpty()) {
            return [];
        }

        $statusToCount = [];

        foreach ($unseenRequestsGroupByStatuses as $request) {
            switch ($request['status']) {
                case OrderRequestStatusStateMachine::CREATED:
                    $statusToCount[OrderRequestStatusStateMachine::CREATED] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::WAITING_CLIENT_RESPONSE:
                    $statusToCount[OrderRequestStatusStateMachine::WAITING_CLIENT_RESPONSE] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT:
                    $statusToCount[OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::IN_PROGRESS:
                    $statusToCount[OrderRequestStatusStateMachine::IN_PROGRESS] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::WAITING_DOCUMENTS:
                    $statusToCount[OrderRequestStatusStateMachine::WAITING_DOCUMENTS] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::COMPLETED:
                    $statusToCount[OrderRequestStatusStateMachine::COMPLETED] = $request["order_request_count"];
                    break;
                case OrderRequestStatusStateMachine::CANCELLED:
                    $statusToCount[OrderRequestStatusStateMachine::CANCELLED] = $request["order_request_count"];
                    break;
            }
        }

        return $statusToCount;
    }

    /**
     * Получаем массив непоказанных откликов сгруппированных по статусу заказа.
     * Массив вида [заказ_статус => количество]
     *
     * @param string $vendorID
     * @return array
     */
    public function getUnseenRequestCountToOrderStatus(string $vendorID): array
    {
        $unseenRequestsCountGroupByOrderStatuses = $this->orderRequestRepository->getUnseenRequestCountToOrderStatus($vendorID);

        if ($unseenRequestsCountGroupByOrderStatuses->isEmpty()) {
            return [];
        }

        $orderStatusToCount = [];

        foreach ($unseenRequestsCountGroupByOrderStatuses as $statusAndCount) {
            switch ($statusAndCount['order_status']) {
                case OrderStatusStateMachine::CREATED:
                    $orderStatusToCount[OrderStatusStateMachine::CREATED] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::VENDOR_SEARCH:
                    $orderStatusToCount[OrderStatusStateMachine::VENDOR_SEARCH] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::WAITING_COMMISSION_PAYMENT:
                    $orderStatusToCount[OrderStatusStateMachine::WAITING_COMMISSION_PAYMENT] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::CREATING_DOCUMENTS:
                    $orderStatusToCount[OrderStatusStateMachine::CREATING_DOCUMENTS] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::LOADING:
                    $orderStatusToCount[OrderStatusStateMachine::LOADING] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::ON_THE_WAY:
                    $orderStatusToCount[OrderStatusStateMachine::ON_THE_WAY] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::WAITING_TO_RECEIVE:
                    $orderStatusToCount[OrderStatusStateMachine::WAITING_TO_RECEIVE] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::WAITING_FULL_PAYMENT:
                    $orderStatusToCount[OrderStatusStateMachine::WAITING_FULL_PAYMENT] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::COMPLETED:
                    $orderStatusToCount[OrderStatusStateMachine::COMPLETED] = $statusAndCount["order_request_count"];
                    break;
                case OrderStatusStateMachine::CANCELLED:
                    $orderStatusToCount[OrderStatusStateMachine::CANCELLED] = $statusAndCount["order_request_count"];
                    break;
            }
        }

        return $orderStatusToCount;
    }

    /**
     * Создаем отклик на заказ и переводим в статус waiting_client_response.
     *
     * @param string $vendorID
     * @param string $orderRequestID
     * @return OrderRequest|null
     * @throws FailedMakeOfferForOrderException
     */
    public function makeOfferForOrder(string $vendorID, string $orderRequestID): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByVendorIDWithOrder($orderRequestID, $vendorID);
        if (!$orderRequest) {
            return null;
        }
        $order = $orderRequest->order;

        $materialID = $this->storageMaterialRepository->firstByID($order->material_id, $orderRequest->vendor_storage_id);

        $materialPrice = $this->calculateSelfPriceForOffer($materialID->cubic_meter_price, $order->quantity);
        $deliveryPrice = $this->calculateDeliveryPriceForOffer($materialID->delivery_cost_per_cubic_meter_per_kilometer, $orderRequest->distance);

        $orderRequest->material_price = $materialPrice;
        $orderRequest->delivery_price = $deliveryPrice;

        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToWaitingClientResponse($orderRequest->status);
            $orderRequest->shown = false;
        } catch (InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedMakeOfferForOrderException();
        }
        DB::commit();

        return $orderRequest;
    }

    /**
     * Утверждаем скидку для отклика, когда скидка в процентах.
     *
     * @param string $vendorID
     * @param string $orderRequestID
     * @param float $discountPercents
     * @return OrderRequest|null
     * @throws FailedMakeDiscountForOrderException
     */
    function givePercentDiscountToOffer(string $vendorID, string $orderRequestID, float $discountPercents): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByVendorID($orderRequestID, $vendorID);
        if (!$orderRequest) {
            return null;
        }

        return $this->giveDiscount($orderRequest, $discountPercents);
    }

    /**
     * Устанавливаем отклику скидку.
     *
     * @param OrderRequest $orderRequest
     * @param float $discountPercents
     * @return OrderRequest|null
     * @throws FailedMakeDiscountForOrderException
     */
    protected function giveDiscount(
        OrderRequest $orderRequest,
        float        $discountPercents,
    ): ?OrderRequest
    {
        $newTotalPrice = $this->calculateNewPriceByPercentsDiscount(
            $orderRequest->material_price,
            $orderRequest->delivery_price,
            $discountPercents
        );
        /**
         * В этом месте может возникать ошибка в скидке +1 рубль в пользу клиента
         */
        $materialPrice = intval($newTotalPrice * 0.7);
        $deliveryPrice = intval($newTotalPrice * 0.3);

        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToWaitingClientResponse($orderRequest->status);
            $orderRequest->discount = $discountPercents;
            $orderRequest->material_price = $materialPrice;
            $orderRequest->delivery_price = $deliveryPrice;
            $orderRequest->is_discounted = true;
        } catch (InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedMakeDiscountForOrderException();
        }
        DB::commit();

        return $orderRequest;
    }


    /**
     * Берем отклик по идентификаторам отклика и поставщика, затем отменяем его.
     * Переводим в статус cancelled.
     *
     * @param string $vendorID
     * @param string $orderRequestID
     * @return OrderRequest|null
     * @throws FailedCancelOrderRequestException
     */
    function cancelOrderRequestByVendorID(string $vendorID, string $orderRequestID): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByVendorID($orderRequestID, $vendorID);
        if (!$orderRequest) {
            return null;
        }
        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToCancel($orderRequest->status);
        } catch (InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedCancelOrderRequestException();
        }
        DB::commit();

        return $orderRequest;
    }

    /**
     * Берем отклик по его идентификатору и отменяем его.
     * Переводим в статус cancelled.
     *
     * @param string $orderRequestID
     * @return void
     * @throws FailedCancelOrderRequestException
     */
    function cancelOrderRequest(string $orderRequestID): void
    {
        $orderRequest = $this->orderRequestRepository->firstByID($orderRequestID);
        if (!$orderRequest) {
            return;
        }
        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToCancel($orderRequest->status);
        } catch (InvalidOrderRequestStatusException $e) {
            return;
        }

        $orderRequestHistory = new OrderRequestHistory(
            [
                'order_request_id' => $orderRequest->id,
                'status' => $orderRequest->status,
                'changed_by' => OrderHistoryChanger::System->value
            ]
        );

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
            $this->orderRequestHistoryRepository->create($orderRequestHistory);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            throw new FailedCancelOrderRequestException();
        }
    }

    /**
     * Отклоняем запрос на скидку.
     *
     * @param string $vendorID
     * @param string $orderRequestID
     * @return OrderRequest|null
     * @throws FailedCancelDiscountRequestException
     */
    function cancelDiscountRequest(string $vendorID, string $orderRequestID): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByVendorID($orderRequestID, $vendorID);
        if (!$orderRequest) {
            return null;
        }
        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToWaitingClientResponse($orderRequest->status);
            $orderRequest->is_discounted = false;
        } catch (InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedCancelDiscountRequestException();
        }
        DB::commit();

        return $orderRequest;
    }

    /**
     * Запрашиваем скидку для отклика.
     *
     * @param string $orderID
     * @param string $orderRequestID
     * @return OrderRequest|null
     * @throws FailedCancelDiscountRequestException
     */
    function askDiscountForOffer(string $orderRequestID, string $orderID): ?OrderRequest
    {
        $orderRequest = $this->orderRequestRepository->firstByOrderID($orderRequestID, $orderID);
        if (!$orderRequest) {
            return null;
        }

        try {
            $orderRequest->status = OrderRequestStatusStateMachine::transitToClientWantDiscount($orderRequest->status);
        } catch (InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedCancelDiscountRequestException();
        }
        DB::commit();

        return $orderRequest;
    }

    /**
     * Получаем запросы с заказами в статусе completed.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCompletedOrderRequestsPaginate(
        string $vendorID,
        int $page,
        int $perPage = 5,
    ): LengthAwarePaginator
    {
        return $this->orderRequestRepository->getWithOrdersByOrderStatusesByVendorIDPaginate(
            $vendorID,
            [OrderRequestStatusStateMachine::COMPLETED],
            $page,
            $perPage
        );
    }

    /**
     * Получаем отклик.
     *
     * @param string $orderRequestID
     * @return OrderRequest|null
     */
    public function firstOrderRequest(string $orderRequestID): ?OrderRequest
    {
        return $this->orderRequestRepository->firstByID($orderRequestID);
    }

    /**
     * Рассчитываем полную, собственную цену для отклика.
     *
     * @param int $materialID
     * @param int $vendorStorageID
     * @param float $distance
     * @param int $quantity
     * @return int
     */
    public function calculateTotalSelfPriceForOffer(
        int   $materialID,
        int   $vendorStorageID,
        float $distance,
        int   $quantity,
    ): int
    {
        $material = $this->storageMaterialRepository->firstByID($materialID, $vendorStorageID);

        $deliveryPrice = $this->calculateDeliveryPriceForOffer($material->delivery_cost_per_cubic_meter_per_kilometer, $distance);
        $materialPrice = $this->calculateSelfPriceForOffer($material->cubic_meter_price, $quantity);

        return $deliveryPrice + $materialPrice;
    }

    /**
     * Вычисляем сцену для отклика, где скидка в процентах.
     *
     * @param int $materialPrice
     * @param int $deliveryPrice
     * @param float $discountPercents Скидка в процентах.
     * @return int
     */
    public function calculateNewPriceByPercentsDiscount(int $materialPrice, int $deliveryPrice, float $discountPercents): int
    {
        $totalPrice = $materialPrice + $deliveryPrice;

        $discount = (int)ceil($totalPrice / 100 * $discountPercents);

        return $totalPrice - $discount;
    }

    /**
     * Вычисляем скидку в процентах, когда нам передали скидку целым числом.
     * Нужно чтобы у отклика уже были рассчитаны material_price и delivery_price.
     *
     * @param int $materialPrice
     * @param int $deliveryPrice
     * @param int $numberDiscount Скидка целым числом.
     * @return float
     */
    public function calculateNewPriceByNumberDiscount(int $materialPrice, int $deliveryPrice, int $numberDiscount): float
    {
        $totalPrice = $materialPrice + $deliveryPrice;

        $part = $totalPrice - $numberDiscount;

        return round(($part / $totalPrice) * 100, 2);
    }

    /**
     * Рассчитываем цену доставки.
     *
     * @param int $deliveryCostPerCubicMeterPerKilometer
     * @param float $distance
     * @return int
     */
    protected function calculateDeliveryPriceForOffer(int $deliveryCostPerCubicMeterPerKilometer, float $distance): int
    {
        return intval(ceil($deliveryCostPerCubicMeterPerKilometer * $distance));
    }

    /**
     * Рассчитываем собственную цену за материал.
     *
     * @param int $cubicMeterPrice
     * @param int $quantity
     * @return int
     */
    protected function calculateSelfPriceForOffer(
        int $cubicMeterPrice,
        int $quantity,
    ): int
    {
        return $cubicMeterPrice * $quantity;
    }

    /**
     * Проставляем не просмотренные отклики как просмотренные.
     *
     * @param LengthAwarePaginator $orderRequestsPaginator
     * @return void
     */
    protected function makeShownOrderRequests(LengthAwarePaginator $orderRequestsPaginator): void
    {
        if ($orderRequestsPaginator->isEmpty()) {
            return;
        }

        $orderRequestIDsToMakeShown = [];
        /** @var OrderRequest $orderRequest */
        foreach ($orderRequestsPaginator as $orderRequest) {
            if ($orderRequest->shown === false) {
                $orderRequestIDsToMakeShown[] = $orderRequest->id;
            }
        }

        $this->orderRequestRepository->makeShown($orderRequestIDsToMakeShown);
    }

}
