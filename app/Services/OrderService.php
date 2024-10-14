<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderHistoryChanger;
use App\Enums\QuestionAnswerType;
use App\Exceptions\FailedCancelOrderException;
use App\Exceptions\FailedConfirmOrderOfferException;
use App\Exceptions\FailedConfirmOrderRequestException;
use App\Exceptions\FailedMakePaymentException;
use App\Exceptions\FailedToChangeOrderQuestionAnswerException;
use App\Exceptions\FailedToCreateOrderException;
use App\Exceptions\InvalidOrderRequestStatusException;
use App\Exceptions\InvalidOrderStatusException;
use App\Models\Delivery;
use App\Models\MaterialQuestion;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderQuestionAnswer;
use App\Models\OrderRequest;
use App\Repositories\Delivery\DeliveryRepositoryInterface;
use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderHistory\OrderHistoryRepositoryInterface;
use App\Repositories\OrderQuestionAnswer\OrderQuestionAnswerRepositoryInterface;
use App\Repositories\OrderRequest\OrderRequestRepository;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepositoryInterface;
use App\StateMachines\OrderRequestStatusStateMachine;
use App\StateMachines\OrderStatusStateMachine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Throwable;

class OrderService
{
    protected MaterialQuestionRepositoryInterface $materialQuestionRepository;
    protected OrderRepositoryInterface $orderRepository;
    protected OrderRequestRepository $orderRequestRepository;
    protected OrderQuestionAnswerRepositoryInterface $orderQuestionAnswerRepository;
    protected OrderHistoryRepositoryInterface $orderHistoryRepository;
    protected OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository;
    protected DeliveryRepositoryInterface $deliveryRepository;
    protected OrderRequestService $orderRequestService;

    public function __construct(
        MaterialQuestionRepositoryInterface $materialQuestionRepository,
        OrderRepositoryInterface            $orderRepository,
        OrderRequestRepository                 $orderRequestRepository,
        OrderQuestionAnswerRepositoryInterface $orderQuestionAnswerRepository,
        OrderHistoryRepositoryInterface     $orderHistoryRepository,
        OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository,
        DeliveryRepositoryInterface         $deliveryRepository,
        OrderRequestService                 $orderRequestService,
    )
    {
        $this->materialQuestionRepository = $materialQuestionRepository;
        $this->orderRepository = $orderRepository;
        $this->orderRequestRepository = $orderRequestRepository;
        $this->orderQuestionAnswerRepository = $orderQuestionAnswerRepository;
        $this->orderHistoryRepository = $orderHistoryRepository;
        $this->orderRequestHistoryRepository = $orderRequestHistoryRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->orderRequestService = $orderRequestService;
    }

    /**
     * Создаем заказ и записываем все ответы на вопросы.
     * При создании заказа присваивается статус 'VENDOR_SEARCH' и записывается в order_history.
     *
     * @param Order $order
     * @param Delivery $delivery
     * @param array $answeredQuestionIDToAnswer
     * @return Order
     * @throws FailedToCreateOrderException
     */
    public function createOrder(
        Order $order,
        Delivery $delivery,
        array    $answeredQuestionIDToAnswer,
    ): Order
    {
        $order->status = OrderStatusStateMachine::VENDOR_SEARCH;

        return DB::transaction(function () use ($order, $delivery, $answeredQuestionIDToAnswer) {
            try {
                $order = $this->orderRepository->create($order);
                $delivery->order_id = $order->id;
                $this->deliveryRepository->create($delivery);
            } catch (Throwable $e) {
                report($e);
                throw new FailedToCreateOrderException();
            }

            $questionsIDs = array_keys($answeredQuestionIDToAnswer);
            $questions = $this->materialQuestionRepository->getActiveByIDs($questionsIDs);

            $questionIDToMaterialQuestionArr = [];
            foreach ($questions as $question) {
                $questionIDToMaterialQuestionArr[$question->id] = $question;
            }

            $answers = [];
            foreach ($answeredQuestionIDToAnswer as $questionID => $valueOrAnswerID) {
                if (!array_key_exists($questionID, $questionIDToMaterialQuestionArr)) {
                    continue;
                }

                /** @var MaterialQuestion $question */
                $question = $questionIDToMaterialQuestionArr[$questionID];

                $answer = $this->makeOrderQuestionAnswer(
                    $order->id,
                    $questionID,
                    $question->question_answer_type,
                    $valueOrAnswerID
                );

                $answers[] = $answer;
            }

            $attributesArray = array_map(function ($answer) {
                return $answer->getAttributes();
            }, $answers);

            if (!$this->orderQuestionAnswerRepository->createMany($attributesArray)) {
                throw new FailedToCreateOrderException();
            }

            $orderHistory = new OrderHistory([
                'order_id' => $order->id,
                'status' => $order->status,
                'changed_by' => OrderHistoryChanger::System->value,
            ]);
            if (!$this->orderHistoryRepository->create($orderHistory)) {
                throw new FailedToCreateOrderException();
            }

            return $order;
        });
    }

    /**
     * Получаем активные заказы с пагинацией.
     *
     * @param string $clientID
     * @param int $page
     * @param int $perPage
     * @return Order[]|LengthAwarePaginator
     */
    public function getActiveOrdersPaginate(string $clientID, int $page, int $perPage = 1): array|LengthAwarePaginator
    {
        $statuses = OrderStatusStateMachine::getActiveStatuses();
        return $this->orderRepository->getByStatusesPaginate($clientID, $page, $statuses, $perPage);
    }

    /**
     * Получаем все заказы с активным статусом.
     *
     * @param string $userID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOrdersInActiveStatuses(string $userID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        $activeStatuses = OrderStatusStateMachine::getActiveStatuses();
        return $this->orderRepository->getByStatuses($userID, $page, $activeStatuses, $perPage);
    }

    /**
     * Получаем заказы в статусе "on_the_way".
     *
     * @param string $userID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOnTheWayOrdersPaginate(string $userID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        $statuses = [OrderStatusStateMachine::ON_THE_WAY];
        return $this->orderRepository->getByStatusesPaginate($userID, $page, $statuses, $perPage);
    }

    /**
     * Получаем все заказы с транзитным статусом.
     *
     * @param string $userID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOrdersInTransitStatuses(string $userID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        $transitStatuses = OrderStatusStateMachine::getTransitStatuses();
        return $this->orderRepository->getByStatuses($userID, $page, $transitStatuses);
    }

    /**
     * Получаем все заказы с архивным статусом.
     *
     * @param string $userID
     * @param int $page
     * @return Order[]|LengthAwarePaginator
     */
    function getOrdersInArchiveStatuses(string $userID, int $page): array|LengthAwarePaginator
    {
        $archiveStatus = OrderStatusStateMachine::getArchiveStatus();
        return $this->orderRepository->getByStatuses($userID, $page, $archiveStatus);
    }

    /**
     * Получаем архивные заказы с пагинацией.
     *
     * @param string $userID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArchiveOrdersPaginate(string $userID, int $page, int $perPage = 1): LengthAwarePaginator
    {
        $archiveStatus = OrderStatusStateMachine::getArchiveStatus();
        return $this->orderRepository->getByStatusesPaginate($userID, $page, $archiveStatus,$perPage);
    }

    /**
     * Получаем все заказы с пагинацией.
     *
     * @param string $clientID
     * @param int $page
     * @param int $perPage
     * @return Order[]|LengthAwarePaginator
     */
    function getOrdersPaginate(string $clientID, int $page, int $perPage = 5): array|LengthAwarePaginator
    {
        return $this->orderRepository->getPaginate($clientID, $page, $perPage);
    }

    /**
     * Получаем заказ по идентификатору и идентификатору клиента.
     *
     * @param string $orderID
     * @param string $clientID
     * @return Order|null
     */
    function getOrderByClientID(string $orderID, string $clientID): ?Order
    {
        return $this->orderRepository->firstByClientID($orderID, $clientID);
    }

    /**
     * Получаем заказ по идентификатору.
     *
     * @param string $orderID
     * @return Order|null
     */
    function firstOrder(string $orderID): ?Order
    {
        return $this->orderRepository->first($orderID);
    }

    /**
     * Удаляет заказ мягким способом.
     *
     * @param string $orderID
     * @param string $clientID
     * @return bool
     */
    function softDeleteOrder(string $orderID, string $clientID): bool
    {
        return $this->orderRepository->softDelete($orderID, $clientID);
    }

    /**
     * Получаем ответы пользователя на выбранный вопрос в заказе.
     *
     * @param string $orderID
     * @param int $questionID
     * @return array|Collection
     */
    function getAnswers(string $orderID, int $questionID): array|Collection
    {
        return $this->orderQuestionAnswerRepository->getAnswers($orderID, $questionID);
    }

    /**
     * Принимаем отклик на заказ.
     *
     * @param string $clientID
     * @param string $orderID
     * @param string $orderRequestID
     * @return Collection|null
     * @throws FailedConfirmOrderOfferException
     */
    public function confirmOrderOffer(string $clientID, string $orderID, string $orderRequestID): ?Collection
    {
        $order = $this->orderRepository->firstByClientID($clientID, $orderID);
        if (is_null($order)) {
            return null;
        }

        $orderRequest = $this->orderRequestRepository->firstByOrderID($orderRequestID, $orderID);
        if (!$orderRequest) {
            return null;
        }

        try {
            $order->status = OrderStatusStateMachine::transitToWaitingCommissionPayment($order->status);
            $orderRequest->status = OrderRequestStatusStateMachine::transitToInProgress($orderRequest->status);
        } catch (InvalidOrderStatusException|InvalidOrderRequestStatusException $e) {
            return null;
        }

        DB::beginTransaction();
        try {
            $this->orderRepository->update($order);
            $this->orderRequestRepository->update($orderRequest);
        } catch (Throwable $e) {
            report($e);
            DB::rollBack();

            throw new FailedConfirmOrderOfferException();
        }
        DB::commit();

        $response = new Collection();
        $response->put('order', $order);
        $response->put('order_request', $orderRequest);

        return $response;
    }

    /**
     * Изменяет ответ пользователя на выбранный вопрос в заказе.
     *
     * @param string $orderID
     * @param int $questionID
     * @param string $questionAnswerType
     * @param string $answerIDOrValue
     * @return mixed|void
     * @throws FailedToChangeOrderQuestionAnswerException
     */
    public function changeOrderQuestionAnswer(
        string $orderID,
        int    $questionID,
        string $questionAnswerType,
        string $answerIDOrValue,
    )
    {
        $orderQuestionAnswer = $this->makeOrderQuestionAnswer($orderID, $questionID, $questionAnswerType, $answerIDOrValue);

        return DB::transaction(function () use ($orderQuestionAnswer, $orderID, $questionID) {
            if (!$this->orderQuestionAnswerRepository->softDeleteAnswers(orderID: $orderID, questionID: $questionID)) {
                throw new FailedToChangeOrderQuestionAnswerException();
            }

            try {
                $this->orderQuestionAnswerRepository->create($orderQuestionAnswer);
            } catch (Throwable $e) {
                report($e);
                throw new FailedToChangeOrderQuestionAnswerException();
            }
        });
    }

    /**
     * Создает объект ответа на вопрос в заказе.
     *
     * @param string $orderID
     * @param int $questionID
     * @param string $questionAnswerType
     * @param string $valueOrAnswerID
     * @return OrderQuestionAnswer
     */
    protected function makeOrderQuestionAnswer(
        string $orderID,
        int    $questionID,
        string $questionAnswerType,
        string $valueOrAnswerID,
    ): OrderQuestionAnswer
    {
        $answer = new OrderQuestionAnswer();

        $answer->order_id = $orderID;
        $answer->material_question_id = $questionID;

        if ($questionAnswerType == QuestionAnswerType::Select->value) {
            $answer->material_question_answer_id = $valueOrAnswerID;
        } else {
            $answer->answer = $valueOrAnswerID;
        }

        return $answer;
    }

    /**
     * Обновляем заказ.
     *
     * @param Order $order
     * @return bool
     * @throws Throwable
     */
    public function updateOrder(Order $order): bool
    {
        return $this->orderRepository->update($order);
    }


    /**
     *  Получаем просроченные заказы до сегодняшнего дня в статусах created, vendor_search, waiting_commission_payment.
     *
     * @return Collection
     */
    public function getExpiredOrders(): LazyCollection
    {
        $status = [
            OrderStatusStateMachine::CREATED,
            OrderStatusStateMachine::VENDOR_SEARCH,
            OrderStatusStateMachine::WAITING_COMMISSION_PAYMENT,
        ];
        $date = Carbon::now()->subDay(1)->toDateString();

        return $this->orderRepository->getUpToDateByLazyChunk($status, $date);
    }

    /**
     * Отменяет заказ.
     *
     * @param Order $order
     * @return void
     * @throws FailedCancelOrderException
     */
    public function cancelOrder(Order $order): void
    {
        DB::beginTransaction();

        try {
            $orderID = $order->id;
            $orderRequests = $this->orderRequestRepository->getByOrderIDWithoutCancelled($orderID);

            foreach ($orderRequests as $orderRequest) {
                    $this->orderRequestService->cancelOrderRequest($orderRequest->id);
            }

            $order->status = OrderStatusStateMachine::transitToCancelled($order->status);
            $orderHistory = new OrderHistory([
                'order_id' => $order->id,
                'status' => OrderStatusStateMachine::CANCELLED,
                'changed_by' => OrderHistoryChanger::System->value,
            ]);

            $this->orderRepository->update($order);
            $this->orderHistoryRepository->create($orderHistory);

            DB::commit();
            return;
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            throw new FailedCancelOrderException();
        }
    }

    /**
     * Возвращаем количество активных заказов.
     *
     * @param string $clientID
     * @return int
     */
    public function countActiveOrders(string $clientID): int
    {
        $statuses = OrderStatusStateMachine::getActiveStatuses();
        return $this->orderRepository->countByStatuses($clientID, $statuses);
    }

    /**
     * Утверждаем отклик для заказа.
     * Перемещаем статус у заказа в WAITING_COMMISSION_PAYMENT и проставляем accepted_request_id.
     *
     * @param Order $order
     * @param string $orderRequestID
     * @return boolean
     */
    public function confirmOrderRequestForOrder(Order $order, string $orderRequestID): bool
    {
        DB::beginTransaction();
        try {           
            /** @var OrderRequest $orderRequest */            
            $orderRequest = $this->orderRequestRepository->getByOrderID($order->id)->first();
            /** @var Order $order */
            $order->status = OrderStatusStateMachine::transitToWaitingCommissionPayment($order->status);
            $order->accepted_request_id = $orderRequestID;
            $orderRequest->status = OrderRequestStatusStateMachine::getNextStatus($orderRequest->status);
            /** @var OrderHistory $orderHistory */
            $orderHistory = new OrderHistory([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'changed_by' => OrderHistoryChanger::System->value,
                ]);

            $this->orderRepository->update($order);
            $this->orderHistoryRepository->create($orderHistory);
            $this->orderRequestRepository->update($orderRequest);
            return true;
        } catch (Throwable $e) {
            report($e);
            throw new FailedConfirmOrderRequestException();
        }
    }

    /**
     * Проводим оплату за заказ и изменяем статус после оплаты на CREATING_DOCUMENTS.
     *
     * @param Order $order
     * @return boolean
     */
    public function makePayment(Order $order): bool
    {
        DB::beginTransaction();
        try {
            
            /** @var OrderRequest $orderRequest */           
            $orderRequest = $this->orderRequestRepository->getByOrderID($order->id)->first();
            /** @var Order $order */
            $order->status = OrderStatusStateMachine::transitToCreatingDocuments($order->status);
            $orderRequest->status = OrderRequestStatusStateMachine::getNextStatus($orderRequest->status);
            /** @var OrderHistory $orderHistory */
            $orderHistory = new OrderHistory([
                'order_id' => $order->id,
                'status' => $order->status,
                'changed_by' => OrderHistoryChanger::System->value,
            ]);

            $this->orderRepository->update($order);
            $this->orderHistoryRepository->create($orderHistory);
            $this->orderRequestRepository->update($orderRequest);
            return true;
        } catch (Throwable $e) {
            report($e);
            throw new FailedMakePaymentException();
        }
    }
}
