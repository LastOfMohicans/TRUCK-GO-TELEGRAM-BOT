<?php

declare(strict_types=1);

namespace App\Telegram\Traits;

use App\Exceptions\FailedCancelOrderException;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * Трейт для отмены заказа.
 * Если у заказа есть отклики, то они тоже отменяются.
 */
trait OrderCancellable
{
    protected OrderService $orderService;

    /**
     * Инициализируем трейт, обязательно для корректной работы.
     *
     * @param OrderService $orderService
     * @return void
     */
    public function initializeOrderCancellableTrait(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    /**
     * Входной метод, который спрашивает пользователя о подтверждении отмены заказа.
     * Если пользователь соглашается, то начинается процесс отмены заказа.
     * В ином случае пользователь возвращается в меню.
     *
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws InvalidArgumentException
     */
    public function startConfirmCancelOrder(Nutgram $bot, string $orderID): void
    {
        $this->clearButtons();

        $this->menuText("Вы точно хотите отменить заĸаз {$orderID}?\nПодтвердите отмену заĸаза.");
        $this->addButtonRow(
            InlineKeyboardButton::make('Да, подтверждаю', callback_data: $orderID . '@handleCancelOrder')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make($this->backButton, callback_data: '@handleShowActiveOrders')
        );

        $this->showMenu();
    }

    /**
     * Отмена заказа и существующих откликов.
     *
     * @param Nutgram $bot
     * @param string $orderID
     * @return void
     * @throws FailedCancelOrderException
     * @throws InvalidArgumentException
     */
    public function handleCancelOrder(Nutgram $bot, string $orderID)
    {
        $order = $this->orderService->firstOrder($orderID);

        $client = Auth::user();
        $this->orderService->cancelOrder($order, $client);

        $this->explainCancelReason($bot);
    }

    /**
     * Вопрос о причине отмены заказа.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function explainCancelReason(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->menuText('Пожалуйста, поделитесь причиной отмены:');
        $this->addButtonRow(
            InlineKeyboardButton::make('Заказал по ошибке', callback_data: '@handleShowActiveOrders')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Не устраивает цена', callback_data: '@handleShowActiveOrders')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Не устраивает поставщик', callback_data: '@handleShowActiveOrders')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Другое', callback_data: '@handleShowActiveOrders')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make('Сделать новый заказ', callback_data: '@handleCreateOrder')
        );
        $this->addButtonRow(
            InlineKeyboardButton::make($this->backButton, callback_data: '@handleShowActiveOrders')
        );


        $this->showMenu();
    }
}
