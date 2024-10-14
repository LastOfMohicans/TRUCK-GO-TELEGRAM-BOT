<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use Illuminate\Pagination\LengthAwarePaginator;
use SergiX44\Nutgram\Nutgram;

class ShowActiveOrders extends ShowOrderListBase
{

    protected string $orderID = '';

    protected int $lastPage = 0;

    protected string $clientID = '';

    public function start(Nutgram $bot, string $chatID)
    {
        $this->showList($bot, $chatID, 1);
    }

    /**
     * @param $page
     * @return array|LengthAwarePaginator
     */
    public function getOrdersPaginate(int $page): array|LengthAwarePaginator
    {
        return $this->getOrderService()->getActiveOrdersPaginate($this->clientID, page: $page);
    }
}
