<?php
declare(strict_types=1);

namespace App\Repositories\Vendor;


use App\Models\Vendor;
use Throwable;

class VendorRepository implements VendorRepositoryInterface
{
    /**
     * @param Vendor $vendor
     * @return Vendor
     * @throws Throwable
     */
    public function create(Vendor $vendor): Vendor
    {
        $vendor->saveOrFail();

        return $vendor;
    }

    /**
     * @param $vendorID
     * @return Vendor|null
     */
    public function getByID($vendorID): ?Vendor
    {
        return Vendor::where('id', $vendorID)->first();
    }

    /**
     * @param string $telegramChatID
     * @return Vendor|null
     */
    public function getByTelegramChatID(string $telegramChatID): ?Vendor
    {
        return Vendor::where('telegram_chat_id', $telegramChatID)->first();
    }

    public function isINNExists(string $inn): bool
    {
        return Vendor::where('inn', $inn)->exists();
    }

    /**
     * Сохраняем изменения информации о поставщике в базу данных, выбрасывая исключения.
     *
     * @param Vendor $vendor
     * @return bool|null
     * @throws Throwable
     */
    public function update(Vendor $vendor): ?bool
    {
        return $vendor->saveOrFail();
    }


    /**
     * Обновляет последнюю активность пользователя.
     *
     * @param string $vendorID
     * @param string $date
     * @return void
     */
    public function updateLastTelegramAction(string $vendorID, string $date): void
    {
        Vendor::where('id', $vendorID)->update(['last_telegram_action' => $date]);
    }
}
