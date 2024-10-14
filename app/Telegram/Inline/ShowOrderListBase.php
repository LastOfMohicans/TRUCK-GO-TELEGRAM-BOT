<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ShowOrderListBase extends OrderBase
{

    protected string $orderID = '';

    protected int $lastPage = 0;

    protected string $clientID = '';

    /**
     * @param int $page
     * @return array|LengthAwarePaginator
     */
    public function getOrdersPaginate(int $page): array|LengthAwarePaginator
    {
        return $this->getOrderService()->getOrdersPaginate($this->clientID, page: $page);
    }

    protected function start(Nutgram $bot, string $chatID)
    {
        $this->showList($bot, $chatID, 1);
    }

    protected function showList(Nutgram $bot, string $chatID, int $page)
    {
        $this->clearButtons();

        $client = Auth::user();
        $this->clientID = $client->id;

        $orders = $this->getOrdersPaginate((int)$page);
        $this->lastPage = $orders->lastPage();

        foreach ($orders as $order) {
            $this->addButtonRow(InlineKeyboardButton::make("Заказ номер $order->id", callback_data: $order->id . "@handleUpdateOrder"));
        }
        $nextPage = $orders->currentPage() + 1;
        $previousPage = $orders->currentPage() - 1;
        $this->addButtonRow(InlineKeyboardButton::make("Следующая страница", callback_data: $nextPage . "@handleNextPage"));
        $this->addButtonRow(InlineKeyboardButton::make("Предыдущая страница", callback_data: $previousPage . "@handleNextPage"));

        $this->menuText('Варианты ответов:');
        $this->showMenu(reopen: true);
    }

    protected function handleNextPage(Nutgram $bot, string $page)
    {
        if ($page > $this->lastPage) {
            $this->showList($bot, strval($this->chatId), $this->lastPage);
            return;
        }
        $this->showList($bot, strval($this->chatId), (int)$page);
    }

    protected function handlePreviousPage(Nutgram $bot, string $page)
    {
        if ($page <= 0) {
            $this->showList($bot, strval($this->chatId), 1);
        }
        $this->showList($bot, strval($this->chatId), (int)$page);
    }

    protected function handleUpdateOrder(Nutgram $bot, string $orderID)
    {
        $this->end();
        EditOrder::begin(
            $bot, userId: $bot->userId(), chatId: $bot->chatId(),
            data: [
                'orderID' => $orderID,
                'chatID' => $bot->chatId(),
            ]
        );

    }
}
