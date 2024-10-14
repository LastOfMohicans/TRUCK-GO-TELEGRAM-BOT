<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

class OrderBase extends InlineMenuBase
{
    public function handleCancelOrder(Nutgram $bot, string $orderID)
    {
        $client = Auth::user();
        $this->cancelOrder($orderID, $client);

        $bot->sendMessage(
            text: "Заказ номер $orderID отменен."
        );

        $this->end();
    }

    protected function cancelOrder(string $orderID, Client $client)
    {
        $this->getOrderService()->softDeleteOrder($orderID, $client->id);
    }

    /**
     * @param string $orderID
     * @param string $clientID
     * @return Order|null
     */
    protected function getOrder(string $orderID, string $clientID): ?Order
    {
        return $this->getOrderService()->getOrderByClientID(orderID: $orderID, clientID: $clientID);
    }

    protected function isOrderExistsForCurrentClient(string $orderID, string $clientID): bool
    {
        if (is_null($this->getOrderService()->getOrderByClientID(orderID: $orderID, clientID: $clientID))) {
            return false;
        }

        return true;
    }

}
