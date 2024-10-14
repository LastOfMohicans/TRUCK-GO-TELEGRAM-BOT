<?php
declare(strict_types=1);

namespace App\Repositories\Client;

use App\Models\Client;
use Throwable;

/**
 * Управляет client сущностью в БД.
 */
interface ClientRepositoryInterface
{

    /**
     * Создаем юзера.
     *
     * @param Client $client
     * @return Client
     * @throws Throwable
     */
    public function create(Client $client): Client;

    /**
     * Получить клиента по идентификатору чата телеграмм.
     * Возвращает null, если не клиент найден.
     *
     * @param string $chatID
     * @return Client|null $client
     */
    public function getByChatID(string $chatID): ?Client;

    /**
     * Обновляет последнюю активность пользователя в телеграмме.
     *
     * @param string $clientID
     * @param string $date
     * @return void
     */
    public function updateLastTelegramAction(string $clientID, string $date): void;
}
