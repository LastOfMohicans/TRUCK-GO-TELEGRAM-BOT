<?php

declare(strict_types=1);

namespace App\Telegram\Inline;


use App\Exceptions\FailedConfirmOrderOfferException;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Services\CatalogService;
use App\Services\DeliveryService;
use App\Services\MaterialService;
use App\Services\OrderService;
use App\Telegram\Traits\ClientMenuSelectable;
use App\Telegram\Traits\Listable;
use App\Telegram\Traits\MaterialSelectable;
use App\Telegram\Traits\OrderCancellable;
use App\Telegram\Traits\OrderDateTimeSelectable;
use Illuminate\Support\Facades\Auth;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;


class ClientMenu extends InlineMenuBase
{
    use Listable;
    use OrderDateTimeSelectable;
    use OrderCancellable;
    use ClientMenuSelectable;
    use MaterialSelectable;

    protected Client $client;

    /**
     * @param OrderService $orderService
     * @param DeliveryService $deliveryService
     */
    public function __construct(OrderService $orderService, DeliveryService $deliveryService, CatalogService $catalogService, MaterialService $materialService)
    {
        parent::__construct();

        $this->client = Auth::user();
        $this->initializeClientMenuSelectableTrait($orderService, $deliveryService);
        $this->initializeMaterialSelectableTrait($catalogService, $materialService);
        $this->initializeOrderCancellableTrait($orderService);
    }

    public function start(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText('Сделайте выбор');

        $this->addButtonRow(InlineKeyboardButton::make('Сделать новый заказ', callback_data: '@handleCreateOrder'));
        $this->addButtonRow(InlineKeyboardButton::make('Активные заказы', callback_data: '@handleShowActiveOrders'));
        $this->addButtonRow(InlineKeyboardButton::make('Активные доставки', callback_data: '@handleShowActiveDeliveries'));
        $this->addButtonRow(InlineKeyboardButton::make('Архив заказов', callback_data: '@handleArchiveOrders'));

        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleConfirmOrderOffer(Nutgram $bot, string $orderRequestID): void
    {
        try {
            $collection = $this->getOrderService()->confirmOrderOffer($this->client->id, $this->activeOrderID, $orderRequestID);
        } catch (FailedConfirmOrderOfferException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if (is_null($collection)) {
            $this->handleShowActiveOrders($bot);
            return;
        }

        /** @var Order $order */
        $order = $collection->get('order');
        /** @var OrderRequest $orderRequest */
        $orderRequest = $collection->get('order_request');

        $totalPrice = $orderRequest->material_price + $orderRequest->delivery_price;
        $prepaid = $totalPrice * 0.15;

        $this->menuText(
            "Отлично! Мы подтвердили заказ {$order->id}
Поставщик уже готовит его к отправке.
Общая стоимость =  {$totalPrice}
Сейчас нужно оплатить небольшую часть в размере 15%, что составит = {$prepaid}
Во избежании фейковых заявок и принятия условий обслуживания сервиса в рамках
данной поставки."
        );

    }

    public function handleShowActiveDeliveries(Nutgram $bot, string $page = "1"): void
    {
        $this->clearButtons();

        $orderDelivery= $this->getOrderService()->getOnTheWayOrdersPaginate($this->client->id, (int)$page,1);
        if ($orderDelivery->isEmpty()) {
            $this->menuText('Заказов в статусе "Доставка" нет!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }

        /** @var Order $order */
        $order = $orderDelivery->items()[0];
        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);

        $msg = $this->makeOrderMessage($order, $material, $delivery);
        $this->menuText('Ваши заказы в доставке: ' . PHP_EOL . $msg);

        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->makeList($orderDelivery, 'handleShowActiveDeliveries');
        $this->showMenu();
    }

    public function handleShowDelivery(Nutgram $bot, string $deliveryID): void
    {
        $this->clearButtons();

        $delivery = $this->deliveryService->firstDeliveryByOrderID($deliveryID);
        if (!$delivery) {
            $this->menuText('Доставка не найдена.');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@handleShowActiveDeliveries'));
            $this->showMenu();
            return;
        }

        $this->menuText(
            "Детали доставки: \n\n" .
            "ID: {$delivery->id}\n" .
            "Широта: {$delivery->latitude}\n" .
            "Долгота: {$delivery->longitude}\n" .
            "Адрес: {$delivery->address}\n" .
            "Время завершения: {$delivery->finish_time}\n" .
            "Создано: {$delivery->created_at}\n" .
            "Обновлено: {$delivery->updated_at}\n" .
            "ID заказа: {$delivery->order_id}\n" .
            "Начало окна доставки: {$delivery->wanted_delivery_window_start}\n" .
            "Конец окна доставки: {$delivery->wanted_delivery_window_end}\n"
        );
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@handleShowActiveDeliveries'));
        $this->showMenu();
    }

    /**
     * Показывает список архивных заказов с пагинацией.
     *
     * @param Nutgram $bot
     * @param $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleArchiveOrders(Nutgram $bot, $page = 1): void
    {
        $this->clearButtons();

        $archiveOrders = $this->getOrderService()->getArchiveOrdersPaginate($this->client->id, (int)$page);
        if ($archiveOrders->isEmpty()) {
            $this->menuText('У вас нет заказов в архиве!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }

        /** @var Order $order */
        $order = $archiveOrders->items()[0];
        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);

        $msg = $this->makeOrderMessage($order, $material, $delivery);
        $this->menuText('Ваши заказы в архиве: ' . PHP_EOL . $msg);

        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->makeList($archiveOrders, 'handleArchiveOrders');
        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function internalHandleAddressAnswer(Nutgram $bot): void
    {
        $this->handleEditAddress($bot);
    }

    /**
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleUpdateDeliveryDate(Nutgram $bot, string $orderID): void
    {
        $this->clearButtons();
        $bot->sendMessage("Изменение даты доставки для заказа: $orderID");
        $this->addButtonRow(InlineKeyboardButton::make("Да, запланировать на другой день . ", callback_data: "@askDate"));
    }

    /**
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleUpdateAddress(Nutgram $bot, string $orderID): void
    {
        $this->activeOrderID = $orderID;
        $this->clearButtons();

        $this->menuText('Введите адрес доставки');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->orNext('handleEditAddress');
        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleEditAddress(Nutgram $bot): void
    {
        $this->clearButtons();

        $order = $this->getOrderService()->firstOrder($this->activeOrderID);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $material = $this->getMaterialService()->firstMaterial($order->material_id);

        $message = $this->generateOrderUpdateMessage($order, $delivery, $material);
        $bot->sendMessage($message);
        $this->addButtonRow(InlineKeyboardButton::make('Да, подтверждаю', callback_data: '@handleSaveAddress'));
        $this->addButtonRow(InlineKeyboardButton::make('Нет, не менять', callback_data: $this->activeOrderID . '@handleEditOrder'));
        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleSaveAddress(Nutgram $bot)
    {
        $this->clearButtons();

        $address = $this->getDeliveryService()->firstDeliveryByOrderID($this->activeOrderID);
        $address->address = $this->address->getAddress();
        $address->latitude = $this->address->getLatitude();
        $address->longitude = $this->address->getLongitude();
        $this->getDeliveryService()->updateDelivery($address);

        $this->menuText($this->orderUpdatedMessage);
        $this->addButtonRow(InlineKeyboardButton::make('Сделать новый заказ', callback_data: '@handleCreateOrder'));
        $this->addButtonRow(InlineKeyboardButton::make('Активные заказы', callback_data: '@handleShowActiveOrders'));
        $this->showMenu();
    }
}
