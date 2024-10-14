<?php
declare(strict_types=1);

namespace App\Repositories\Vendor;

use App\Models\Vendor;
use Throwable;

/**
 * Управляет vendor сущностью в БД.
 */
interface VendorRepositoryInterface
{
    /**
     * Создаем поставщика.
     *
     * @param Vendor $vendor
     * @return Vendor
     * @throws Throwable
     */
    public function create(Vendor $vendor): Vendor;


    /**
     * Получаем поставщика.
     * Если не найден то возвращает null.
     *
     * @param string $vendorID
     * @return Vendor|null
     */
    public function getByID(string $vendorID): ?Vendor;

    /**
     * Получаем поставщика с помощью идентификатора чата телеграмм.
     * Если не найден то возвращает null.
     *
     * @param string $telegramChatID
     * @return Vendor|null
     */
    public function getByTelegramChatID(string $telegramChatID): ?Vendor;

    /**
     * Проверяем, существует ли заданный ИНН.
     *
     * @param string $inn
     * @return bool
     */
    public function isINNExists(string $inn): bool;

    /**
     * Обновляем сущность поставщика.
     *
     * @param Vendor $vendor
     * @return bool|null
     * @throws Throwable
     */
    public function update(Vendor $vendor): ?bool;

    /**
     * Обновляет последнюю активность пользователя.
     *
     * @param string $vendorID
     * @param string $date
     * @return void
     */
    public function updateLastTelegramAction(string $vendorID, string $date): void;
}
