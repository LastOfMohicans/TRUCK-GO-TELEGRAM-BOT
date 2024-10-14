<?php
declare(strict_types=1);

namespace App\StateMachines;

use App\Exceptions\InvalidOrderRequestStatusException;

/**
 * Определяет статусы, которые может иметь отклик и его переходы.
 */
class OrderRequestStatusStateMachine
{
    const string CREATED = 'created'; // Ожидание обработки заявки от поставщика.
    const string WAITING_CLIENT_RESPONSE = 'waiting_client_response'; // Ожидание ответа от клиента.
    const string CLIENT_WANT_DISCOUNT = 'client_want_discount'; // Клиент хочет скидку.
    const string IN_PROGRESS = 'in_progress'; // Когда клиент принял отклик, ожидаем пока заказ будет выполнен.
    const string WAITING_DOCUMENTS = 'waiting_documents'; // Ожидание получения документов менеджером.
    const string COMPLETED = 'completed'; // Успешно завершено.
    const string CANCELLED = 'cancelled'; // Запрос был отменен.

    private static array $statuses = [
        self::CREATED, self::WAITING_CLIENT_RESPONSE, self::CLIENT_WANT_DISCOUNT, self::IN_PROGRESS,
        self::WAITING_DOCUMENTS, self::COMPLETED,
        self::CANCELLED,
    ];

    /**
     * Возможные переходы из статуса.
     *
     * @var array
     */
    private static array $transitions = [
        self::CREATED => [self::WAITING_CLIENT_RESPONSE, self::CANCELLED],
        self::WAITING_CLIENT_RESPONSE => [self::WAITING_CLIENT_RESPONSE, self::CLIENT_WANT_DISCOUNT, self::IN_PROGRESS, self::CANCELLED],
        self::CLIENT_WANT_DISCOUNT => [self::WAITING_CLIENT_RESPONSE, self::CANCELLED],
        self::IN_PROGRESS => [self::WAITING_DOCUMENTS, self::CANCELLED],
        self::WAITING_DOCUMENTS => [self::COMPLETED, self::CANCELLED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];

    /**
     * Переходы при успешном флоу.
     *
     * @var array
     */
    private static array $positiveTransitions = [
        self::CREATED => self::WAITING_CLIENT_RESPONSE,
        self::CLIENT_WANT_DISCOUNT => self::WAITING_CLIENT_RESPONSE,
        self::WAITING_CLIENT_RESPONSE => self::IN_PROGRESS,
        self::IN_PROGRESS => self::WAITING_DOCUMENTS,
        self::WAITING_DOCUMENTS => self::COMPLETED,
    ];


    /**
     * Метод для получения доступных следующих статусов на основе текущего.
     *
     * @param $currentStatus
     * @return array
     * @throws InvalidOrderRequestStatusException
     */
    public static function getPossibleNextStatuses($currentStatus): array
    {
        if (!isset(self::$transitions[$currentStatus])) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return self::$transitions[$currentStatus];
    }

    /**
     * Получаем следующий статус при успешном флоу.
     *
     * @param $currentStatus
     * @return string
     * @throws InvalidOrderRequestStatusException
     */
    public static function getNextStatus($currentStatus): string
    {
        if (!isset(self::$positiveTransitions[$currentStatus])) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return self::$positiveTransitions[$currentStatus];
    }

    /**
     * Проверка существует ли статус.
     *
     * @param $currentStatus
     * @return true
     */
    public static function isStatusExists($currentStatus): bool
    {
        if (in_array($currentStatus, self::$statuses)) {
            return true;
        }

        return false;
    }

    /**
     * Получаем статусы, в которых клиент может откликнуться на запрос.
     *
     * @return string[]
     */
    public static function getStatusesWhichClientCanAccept(): array
    {
        return [
            self::WAITING_CLIENT_RESPONSE, self::CLIENT_WANT_DISCOUNT,
        ];
    }

    /**
     * Проверяем можем ли мы перевести статус в WAITING_CLIENT_RESPONSE.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderRequestStatusException
     */
    public static function transitToWaitingClientResponse(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];
        $nextStatus = self::getNextStatus($currentStatus);

        if (!in_array(self::WAITING_CLIENT_RESPONSE, $possibleStatusesToTransit)) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return $nextStatus;
    }

    /**
     * Проверяем можем ли мы перевести статус в CLIENT_WANT_DISCOUNT.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderRequestStatusException
     */
    public static function transitToClientWantDiscount(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];

        if (!in_array(self::CLIENT_WANT_DISCOUNT, $possibleStatusesToTransit)) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return self::CLIENT_WANT_DISCOUNT;
    }

    /**
     * Проверяем можем ли мы перевести статус в IN_PROGRESS.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderRequestStatusException
     */
    public static function transitToInProgress(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];
        $nextStatus = self::getNextStatus($currentStatus);

        if (!in_array(self::IN_PROGRESS, $possibleStatusesToTransit)) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return $nextStatus;
    }

    /**
     * Проверяем можем ли мы перевести статус в CANCELLED.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderRequestStatusException
     */
    public static function transitToCancel(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];

        if (!in_array(self::CANCELLED, $possibleStatusesToTransit)) {
            throw new InvalidOrderRequestStatusException($currentStatus);
        }

        return self::CANCELLED;
    }
}

