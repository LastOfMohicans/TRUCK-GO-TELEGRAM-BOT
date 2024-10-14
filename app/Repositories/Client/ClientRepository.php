<?php
declare(strict_types=1);

namespace App\Repositories\Client;


use App\Models\Client;
use Throwable;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @param Client $client
     * @return Client
     * @throws Throwable
     */
    function create(Client $client): Client
    {
        $client->saveOrFail();

        return $client;
    }

    /**
     * @param string $chatID
     * @return Client|null
     */
    function getByChatID(string $chatID): ?Client
    {
        return Client::where('telegram_chat_id', $chatID)->first();
    }

    /**
     * Обновляет последнюю активность пользователя в телеграмме.
     *
     * @param string $clientID
     * @param string $date
     * @return void
     */
    public function updateLastTelegramAction(string $clientID, string $date): void
    {
        Client::where('id', $clientID)->update(['last_telegram_action' => $date]);
    }

}
