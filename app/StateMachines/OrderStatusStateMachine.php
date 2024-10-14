<?php
declare(strict_types=1);

namespace App\StateMachines;

use App\Exceptions\InvalidOrderStatusException;

/**
 * Определяет статусы, которые может иметь заказ и его переходы.
 */
class OrderStatusStateMachine
{
    const string CREATED = 'created'; // создан
    const string VENDOR_SEARCH = 'vendor_search'; // в поиске поставщика
    const string WAITING_COMMISSION_PAYMENT = 'waiting_commission_payment'; // ожидание оплаты комиссии
    const string CREATING_DOCUMENTS = 'creating_documents'; // формирование документов по заказу для поставщика
    const string LOADING = 'loading'; // погрузка заказа
    const string ON_THE_WAY = 'on_the_way'; // в пути
    const string WAITING_TO_RECEIVE = 'waiting_to_receive'; // ожидание получения заказа клиентом
    const string WAITING_FULL_PAYMENT = 'waiting_full_payment'; // ожидание полной оплаты заказа
    const string COMPLETED = 'completed'; // завершен
    const string CANCELLED = 'cancelled'; // отменен


    private static array $statuses = [
        self::CREATED, self::VENDOR_SEARCH, self::WAITING_COMMISSION_PAYMENT,
        self::CREATING_DOCUMENTS, self::LOADING, self::ON_THE_WAY,
        self::WAITING_TO_RECEIVE, self::WAITING_FULL_PAYMENT,
        self::COMPLETED, self::CANCELLED,
    ];

    /**
     *  Возможные переходы из статуса
     *
     * @var array
     */
    private static array $transitions = [
        self::CREATED => [self::VENDOR_SEARCH, self::CANCELLED],
        self::VENDOR_SEARCH => [self::WAITING_COMMISSION_PAYMENT, self::CANCELLED],
        self::WAITING_COMMISSION_PAYMENT => [self::CREATING_DOCUMENTS, self::CANCELLED],
        self::CREATING_DOCUMENTS => [self::LOADING, self::CANCELLED],
        self::LOADING => [self::ON_THE_WAY, self::CANCELLED],
        self::ON_THE_WAY => [self::WAITING_TO_RECEIVE, self::CANCELLED],
        self::WAITING_TO_RECEIVE => [self::WAITING_FULL_PAYMENT, self::CANCELLED],
        self::WAITING_FULL_PAYMENT => [self::COMPLETED, self::CANCELLED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];
    /**
     * Переходы при обычном флоу
     *
     * @var array
     */
    private static array $positiveTransitions = [
        self::CREATED => self::VENDOR_SEARCH,
        self::VENDOR_SEARCH => self::WAITING_COMMISSION_PAYMENT, // пре подтверждение поставщика
        self::WAITING_COMMISSION_PAYMENT => self::CREATING_DOCUMENTS, // при оплате комиссии
        self::CREATING_DOCUMENTS => self::LOADING, // пре формирование документов
        self::LOADING => self::ON_THE_WAY, // при загрузке заказа
        self::ON_THE_WAY => self::WAITING_TO_RECEIVE, // пре прибытие на место выдачи
        self::WAITING_TO_RECEIVE => self::WAITING_FULL_PAYMENT, // при выдаче заказа
        self::WAITING_FULL_PAYMENT => self::COMPLETED, // при полной оплате
    ];

    /**
     * Метод для получения доступных следующих статусов на основе текущего.
     *
     * @param $currentStatus
     * @return array
     */
    public static function getPossibleNextStatuses($currentStatus): array
    {
        if (isset(self::$transitions[$currentStatus])) {
            return self::$transitions[$currentStatus];
        }

        return [];
    }

    /**
     * Получаем следующий статус при обычном флоу
     *
     * @param $currentStatus
     * @return mixed|string
     * @throws InvalidOrderStatusException
     */
    public static function getNextStatus($currentStatus): mixed
    {
        if (!isset(self::$positiveTransitions[$currentStatus])) {
            throw new InvalidOrderStatusException($currentStatus);
        }

        return self::$positiveTransitions[$currentStatus];
    }

    /**
     * @param $currentStatus
     * @return bool
     */
    public static function canCancel($currentStatus): bool
    {
        $possibleStatuses = self::getPossibleNextStatuses($currentStatus);
        if (in_array(self::CANCELLED, $possibleStatuses)) {
            return true;
        }

        return false;
    }

    /**
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
     * Получаем статусы в которых заказ считается активным.
     *
     * @return string[]
     */
    public static function getActiveStatuses(): array
    {
        return [self::CREATED, self::VENDOR_SEARCH];
    }

    /**
     * Получаем статусы, в которых запрос считается транзитным.
     *
     * @return string[]
     */
    public static function getTransitStatuses(): array
    {
        return [
            self::CREATING_DOCUMENTS, self::LOADING,
            self::ON_THE_WAY, self::WAITING_TO_RECEIVE,
            self::WAITING_FULL_PAYMENT,
        ];
    }

    /**
     * Получаем статусы, в которых запрос считается архивным.
     *
     * @return string[]
     */
    public static function getArchiveStatus(): array
    {
        return [
            self::COMPLETED,
        ];
    }

    /**
     * Проверяем можем ли мы перевести статус в WAITING_COMMISSION_PAYMENT.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderStatusException
     */
    public static function transitToWaitingCommissionPayment(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];
        $nextStatus = self::getNextStatus($currentStatus);

        if (!in_array(self::WAITING_COMMISSION_PAYMENT, $possibleStatusesToTransit)) {
            throw new InvalidOrderStatusException($currentStatus);
        }

        return $nextStatus;
    }

    /**
     * Проверяем можем ли мы перевести статус в CREATING_DOCUMENTS.
     * Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderStatusException
     */
    public static function transitToCreatingDocuments(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];
        $nextStatus = self::getNextStatus($currentStatus);

        if (!in_array(self::CREATING_DOCUMENTS, $possibleStatusesToTransit)) {
            throw new InvalidOrderStatusException($currentStatus);
        }

        return $nextStatus;
    }

    /**
     *  Проверяем можем ли мы перевести статус в CANCELLED.
     *  Иначе выбрасываем исключение.
     *
     * @param string $currentStatus
     * @return string
     * @throws InvalidOrderStatusException
     */
    public static function transitToCancelled(string $currentStatus): string
    {
        $possibleStatusesToTransit = self::$transitions[$currentStatus];

        if (!in_array(self::CANCELLED, $possibleStatusesToTransit)) {
            throw new InvalidOrderStatusException($currentStatus);
        }

        return self::CANCELLED;
    }


}

