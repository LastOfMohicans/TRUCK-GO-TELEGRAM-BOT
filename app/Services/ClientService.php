<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FailedToCreateClientException;
use App\Models\Client;
use App\Repositories\Client\ClientRepositoryInterface;
use Carbon\Carbon;
use Throwable;

class ClientService
{
    protected ClientRepositoryInterface $clientRepository;

    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * Создаем юзера.
     *
     * @param Client $client
     * @return Client|null
     * @throws FailedToCreateClientException
     */
    function createClient(Client $client): ?Client
    {
        try {
            return $this->clientRepository->create($client);
        } catch (Throwable $e) {
            report($e);
            throw new FailedToCreateClientException();
        }
    }

    /**
     * Получить клиента по идентификатору чата телеграмм.
     * Возвращает null, если не найден.
     *
     * @param string $chatID
     * @return Client|null
     */
    function getClientByChatID(string $chatID): ?Client
    {
        return $this->clientRepository->getByChatID($chatID);
    }


    /**
     * Обновляет последнюю активность пользователя.
     *
     * @param string $clientID
     * @return void
     */
    public function updateLastTelegramAction(string $clientID): void
    {
        $nowTime = Carbon::now()->toDateTimeString();
        $this->clientRepository->updateLastTelegramAction($clientID, $nowTime);
    }

}
