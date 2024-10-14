<?php
declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Exceptions\FailedCancelOrderRequestException;
use App\Exceptions\FailedMakeOfferForOrderException;
use App\Models\Vendor;
use App\Services\OrderRequestService;
use App\Services\OrderService;
use Auth;
use SergiX44\Nutgram\Nutgram;

class OrderRequestController
{

    protected OrderRequestService $orderRequestService;
    protected OrderService $orderService;

    public function __construct(OrderRequestService $orderRequestService, OrderService $orderService)
    {
        $this->orderRequestService = $orderRequestService;
        $this->orderService = $orderService;
    }

    /**
     * Делаем отклик на заказ по цене поставщика.
     *
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     */
    function respondToOrderCallbackQuery(Nutgram $bot, string $orderRequestID): void
    {
        if (empty($orderRequestID)) {
            return;
        }

        /** @var Vendor $vendor */
        $vendor = Auth::user();

        try {
            $orderRequest = $this->orderRequestService->makeOfferForOrder($vendor->id, $orderRequestID);
        } catch (FailedMakeOfferForOrderException $e) {
            $bot->answerCallbackQuery();
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if (is_null($orderRequest)) {
            $bot->answerCallbackQuery();
            return;
        }

        $order = $this->orderService->firstOrder($orderRequest->order_id);
        $bot->answerCallbackQuery(text: 'Вы успешно откликнулись на заказ.');
        $bot->sendMessage("Отклик по Заказу $order->id по ценам сервиса, отправлен клиенту.
Каĸ только клиент даст обратную связь, вы увидите это в разделе «Активные заказы»", $bot->chatId());
    }

    /**
     * Отклоняем заказ.
     *
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     */
    function cancelOrderCallbackQuery(Nutgram $bot, string $orderRequestID): void
    {
        if (empty($orderRequestID)) {
            return;
        }

        /** @var Vendor $vendor */
        $vendor = Auth::user();

        try {
            $orderRequest = $this->orderRequestService->cancelOrderRequestByVendorID($vendor->id, $orderRequestID);
        } catch (FailedCancelOrderRequestException $e) {
            $bot->answerCallbackQuery();
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if (is_null($orderRequest)) {
            $bot->answerCallbackQuery();
            return;
        }

        $order = $this->orderService->firstOrder($orderRequest->order_id);
        $bot->answerCallbackQuery(text: "Вы отказались от заказа {$order->id}.");
    }

}
