<?php
declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Exceptions\FailedMakeOfferForOrderException;
use App\Models\Vendor;
use App\Services\OrderRequestService;
use App\Services\OrderService;
use Auth;
use SergiX44\Nutgram\Nutgram;

class OrderController
{

    protected OrderRequestService $orderRequestService;
    protected OrderService $orderService;

    public function __construct(OrderRequestService $orderRequestService, OrderService $orderService)
    {
        $this->orderRequestService = $orderRequestService;
        $this->orderService = $orderService;
    }

    /**
     * .
     *
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     */
    function respondToOrderRequestCallbackQuery(Nutgram $bot, string $orderRequestID): void
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
}
