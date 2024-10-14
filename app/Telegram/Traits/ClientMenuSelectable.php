<?php

namespace App\Telegram\Traits;

use App\Exceptions\FailedCancelDiscountRequestException;
use App\Models\Delivery;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\StateMachines\OrderRequestStatusStateMachine;
use App\Telegram\Inline\CreateOrder;
use Carbon\Carbon;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Throwable;

trait ClientMenuSelectable
{
    protected OrderService $orderService;

    protected DeliveryService $deliveryService;

    private Order $order;
    public string $activeOrderID;
    public string $orderRequestID;
    public string $currentPage;
    public string $orderUpdatedMessage = 'Отлично, заĸаз изменен! Я уже ищу поставщиĸа. Каĸ тольĸо поставщиĸ подтвердит заĸаз, я
пришлю вам уведомление! А поĸа можно заняться любимым делом.';


    public function initializeClientMenuSelectableTrait(OrderService $orderService, DeliveryService $deliveryService): void
    {
        $this->orderService = $orderService;
        $this->deliveryService = $deliveryService;
    }

    public function handleCreateOrder(Nutgram $bot): void
    {
        $this->end();
        CreateOrder::begin($bot);
    }

    /**
     * Обработка возврата к заказу.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleReturnToOrders(Nutgram $bot): void
    {
        $this->handleShowActiveOrders($bot, $this->currentPage);
    }

    /**
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleShowActiveOrders(Nutgram $bot, string $page = "1"): void
    {
        $this->currentPage = $page;

        $this->clearButtons();

        $activeOrders = $this->getOrderService()->getActiveOrdersPaginate($this->client->id, (int)$page);
        if ($activeOrders->isEmpty()) {
            $this->menuText('У вас нет активных заказов!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }

        /** @var Order $order */
        $order = $activeOrders->items()[0];
        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $this->addButtonRow(InlineKeyboardButton::make('Предложения по заказу', callback_data: $order->id . '@handleShowOrderOffersByOne'));
        $this->addButtonRow(InlineKeyboardButton::make('Изменить заказ', callback_data: $order->id . '@handleEditOrder'));

        $msg = $this->makeOrderMessage($order, $material, $delivery);
        $this->menuText($msg);

        $this->makeList($activeOrders, 'handleShowActiveOrders');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@backToMenu'));

        $this->showMenu();
    }

    /**
     * Делаем валидацию заказа и сохраняем его идентификатор в кеш.
     *
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public
    function handleShowOrderOffersByOne(
        Nutgram $bot,
        string $orderID,
    ): void
    {
        if (empty($orderID)) {
            $this->handleShowActiveOrders($bot);
            return;
        }

        $order = $this->getOrderService()->getOrderByClientID($orderID, $this->client->id);
        if (is_null($order)) {
            $this->handleShowActiveOrders($bot);
            return;
        }
        $this->activeOrderID = $order->id;

        $this->ShowOrderOffersByOne($bot);
    }

    /**
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function ShowOrderOffersByOne(Nutgram $bot, string $page = "1"): void
    {
        if ($this->activeOrderID == "") {
            $this->handleShowActiveOrders($bot);
            return;
        }

        $this->currentPage = $page;

        $this->clearButtons();
        try {
            $orderRequests = $this->getOrderRequestService()->getRequestsWhichClientCanAcceptWithOrdersByOrderIDPaginate($this->activeOrderID, (int)$page);
        } catch (Throwable $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if ($orderRequests->isEmpty()) {
            $this->menuText('Откликов пока что нет.');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu(reopen: true);
            return;
        }

        /** @var OrderRequest $orderRequest */
        $this->orderRequestID = $orderRequests->items()[0];
        /** @var Order $order */
        $order = $orderRequest->order;
        $this->order = $order;

        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($this->orderID);

        $msg = $this->makeOfferMessage($order, $orderRequest, $material, $delivery);

        $this->addButtonRow(
            InlineKeyboardButton::make('Подтвердить', callback_data: "@handleAcceptOrderRequest")
        );
        if ($orderRequest->status != OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT && is_null($orderRequest->is_discounted)) {
            $this->addButtonRow(
                InlineKeyboardButton::make('Хочу скидку', callback_data: "{$orderRequest->id}@handleRequestDiscount")
            );
        }

        $this->makeList($orderRequests, 'ShowOrderOffersByOne');
        $this->menuText($msg);
        $this->showMenu(reopen: true);
    }

    /**
     * Подтверждаем статус оплаты.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handleAcceptOrderRequest(Nutgram $bot): void
    {
        $confirm = $this->orderService->confirmOrderRequestForOrder($this->order, $this->orderRequestID);

        if (!$confirm) {
            $this->menuText('Статус оплаты не подтверждён!');
            $this->showMenu(reopen: true);
            return;
        }

        $this->addButtonRow(
            InlineKeyboardButton::make('Оплатить', callback_data: "@handlePayment")
        );

    }

    /**
     * Оплата заказа.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handlePayment(Nutgram $bot): void
    {
        $payment = $this->orderService->makePayment($this->order);

        if (!$payment) {
            $this->menuText('Оплата не прошла!');
            $this->showMenu(reopen: true);
            return;
        }
        /** @var OrderRequest $orderRequest */
        $currentTime = Carbon::now();
        $deliveryTime = $orderRequest->delivery_duration_minutes;
        $msg = "Отлично, оплата произведена.Примерное время приезда ТС = $currentTime + $deliveryTime + 30мин.
                Отследить статус заказа или посмотреть где едет водитель, можно в разделе активные доставки.";
        $this->menuText($msg);
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleRequestDiscount(Nutgram $bot, string $orderRequestID): void
    {
        try {
            $this->getOrderRequestService()->askDiscountForOffer($orderRequestID, $this->activeOrderID);
        } catch (FailedCancelDiscountRequestException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }

        $bot->sendMessage('Вы успешно запросили скидку');

        $this->ShowOrderOffersByOne($bot, $this->currentPage);
    }

    /**
     * Создаем сообщения для отклика на поставщика.
     *
     * @param Order $order
     * @param OrderRequest $orderRequest
     * @param Material $material
     * @param Delivery $delivery
     * @return string
     */
    protected function makeOfferMessage(
        Order        $order,
        OrderRequest $orderRequest,
        Material     $material,
        Delivery     $delivery,
    ): string
    {
        $totalPrice = $orderRequest->material_price + $orderRequest->delivery_price;
        $msg = "По заказу №: $order->id" . PHP_EOL;
        $msg .= "Статус заказа: {$order->status}" . PHP_EOL;
        $msg .= "Дата доставки: {$delivery->wanted_delivery_window_start}-{$delivery->wanted_delivery_window_end}" . PHP_EOL;
        $msg .= "Адрес доставки: {$delivery->address}" . PHP_EOL;
        $msg .= "Товар: {$material->full_name}" . PHP_EOL;
        $msg .= "****" . PHP_EOL;
        $msg .= "Поставщик: {$order->status}" . PHP_EOL; //Наименование соĸращенное
        $msg .= "Рейтинг: " . PHP_EOL;
        $msg .= "Количество оценок: " . PHP_EOL;
        $msg .= "Количество отгрузок: " . PHP_EOL;
        $msg .= "Время доставки: " . PHP_EOL; //ТЕКУЩЕЕ ВРЕМЯ + Order #1/Response/Response#1/Delivery time + 30мин
        $msg .= "Итоговая стоимость: {$totalPrice}" . PHP_EOL;

        return $msg;
    }

    /**
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleEditOrder(Nutgram $bot, string $orderID): void
    {
        $this->activeOrderID = $orderID;
        $this->clearButtons();

        $order = $this->getOrderService()->firstOrder($orderID);
        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $msg = $this->makeOrderMessage($order, $material, $delivery);
        $this->menuText($msg);

        $this->addButtonRow(
            InlineKeyboardButton::make('Изменить дату доставĸи', callback_data: 'handleConfirmDate' . '@askOrderDeliveryDateTime')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Изменить адрес', callback_data: '@handleAskDate')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Изменить товар', callback_data: '@handleSelectProduct')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Отменить товар', callback_data: $orderID . '@handleConfirmCancelOrder')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make($this->backButton, callback_data: '@handleReturnToOrders')
        );

        $this->showMenu();
    }

    /**
     * Запускает изменение товара.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleSelectProduct(Nutgram $bot): void
    {
        $this->startSelectMaterial($bot, 'handleConfirmProduct');
    }

    /**
     * Метод для входа в трейт, отменяющий заказ.
     *
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleConfirmCancelOrder(Nutgram $bot, string $orderID)
    {
        $this->startConfirmCancelOrder($bot, $orderID);
    }

    /**
     * Метод для подтверждения изменения даты выбранного товара.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleConfirmDate(Nutgram $bot): void
    {
        $this->clearButtons();
        $order = $this->getOrderService()->firstOrder($this->activeOrderID);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $material = $this->getMaterialService()->firstMaterial($order->material_id);

        $message = $this->generateOrderUpdateMessage($order, $delivery, $material);
        $bot->sendMessage($message);
        $this->addButtonRow(InlineKeyboardButton::make('Да, подтверждаю', callback_data: '@handleSaveDateChange'));
        $this->addButtonRow(
            InlineKeyboardButton::make('Нет, не менять', callback_data: $this->activeOrderID . '@handleEditOrder')
        );
        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAskDate(Nutgram $bot): void
    {
        $this->askAddress($bot);
    }

    /**
     * Метод для сохранения выбранной даты.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleSaveDateChange(Nutgram $bot): void
    {
        $this->clearButtons();
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($this->activeOrderID);
        $delivery->wanted_delivery_window_start = $this->wanted_delivery_window_start;
        $delivery->wanted_delivery_window_end = $this->wanted_delivery_window_end;
        $this->getDeliveryService()->updateDelivery($delivery);

        $this->menuText($this->orderUpdatedMessage);
        $this->addButtonRow(InlineKeyboardButton::make('Сделать новый заĸаз', callback_data: '@handleCreateOrder'));
        $this->addButtonRow(InlineKeyboardButton::make('Аĸтивные заĸазы', callback_data: '@handleShowActiveOrders'));
        $this->showMenu();
    }

    /**
     * Метод для подтверждения изменений выбранного товара.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleConfirmProduct(Nutgram $bot): void
    {
        $this->clearButtons();
        $order = $this->getOrderService()->firstOrder($this->activeOrderID);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $material = $this->getMaterialService()->firstMaterial($order->material_id);

        $message = $this->generateOrderUpdateMessage($order, $delivery, $material);
        $bot->sendMessage($message);
        $this->addButtonRow(InlineKeyboardButton::make('Да, подтверждаю', callback_data: '@handleSaveProductChange'));
        $this->addButtonRow(
            InlineKeyboardButton::make('Нет, не менять', callback_data: $this->activeOrderID . '@handleEditOrder')
        );
        $this->showMenu(true);
    }

    /**
     * Метод для сохранения выбранного товара.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function handleSaveProductChange(Nutgram $bot)
    {
        $this->clearButtons();

        $order = $this->getOrderService()->firstOrder($this->activeOrderID);
        $order->material_id = $this->materialID;
        $order->quantity = $this->quantity;
        $this->getOrderService()->updateOrder($order);

        $this->menuText($this->orderUpdatedMessage);
        $this->addButtonRow(InlineKeyboardButton::make('Сделать новый заĸаз', callback_data: '@handleCreateOrder'));
        $this->addButtonRow(InlineKeyboardButton::make('Аĸтивные заĸазы', callback_data: '@handleShowActiveOrders'));
        $this->showMenu();
    }

    /**
     * Метод для показа изменений в выбранном товаре.
     *
     * @param Order $order
     * @param Delivery $delivery
     * @param Material $material
     * @return string
     */
    private function generateOrderUpdateMessage(
        Order $order,
        Delivery $delivery,
        Material $material,
    ): string
    {
        $address = $this->address ? $this->address->getAddress() : $delivery->address;
        $product = !empty($this->materialName) ? $this->materialName : $material->name;
        $dateStart = $this->wanted_delivery_window_start ?? $delivery->wanted_delivery_window_start;
        $dateEnd = $this->wanted_delivery_window_end ?? $delivery->wanted_delivery_window_end;

        return "Изменение по заĸазу" . PHP_EOL .
            "Заĸаз № = {$order->id} изменен." . PHP_EOL .
            "Детали заĸаза:" . PHP_EOL .
            "Номер заĸаза: = {$order->id}" . PHP_EOL .
            "Статус: {$order->status}" . PHP_EOL .
            "Дата заказа: {$this->formatDate($dateStart)}" . PHP_EOL .
            "Время заказа: {$this->formatTime($dateStart,$dateEnd)}" . PHP_EOL .
            "Адрес доставĸи: = {$address}" . PHP_EOL .
            "Товары: = {$product}" . PHP_EOL .
            "Подтвердить изменение?";
    }

    /**
     * Метод, который форматирует дату в такой вид 2024-07-28.
     *
     * @param $date
     * @return string
     */
    private function formatDate($date): string
    {
        return date("Y-m-d", strtotime($date));
    }

    /**
     * Метод, который принимает 2 даты и возвращает диапазон времени.
     *
     * @param $startDate
     * @param $endDate
     * @return string
     */
    private function formatTime($startDate, $endDate): string
    {
        $startUnixTime = strtotime($startDate);
        $endUnixTime = strtotime($endDate);
        return date("H:i", $startUnixTime) . '-' . date("H:i", $endUnixTime);
    }

    /**
     * Создаем сообщения для показа заказа.
     *
     * @param Order $order
     * @param Material $material
     * @param Delivery $delivery
     * @return string
     */
    protected function makeOrderMessage(
        Order    $order,
        Material $material,
        Delivery $delivery,
    ): string
    {
        $msg = "По заказу №: $order->id" . PHP_EOL;
        $msg .= "Статус заказа: {$order->status}" . PHP_EOL;
        $msg .= "Дата доставки: {$delivery->wanted_delivery_window_start}:{$delivery->wanted_delivery_window_end}" . PHP_EOL;
        $msg .= "Адрес доставки: {$delivery->address}" . PHP_EOL;
        $msg .= "Товар: {$material->full_name}" . PHP_EOL;

        return $msg;
    }


}
