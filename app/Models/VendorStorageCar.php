<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *  Модель VendorStorageCar
 *
 * Определяет сущность автомобиля на складе поставщика.
 *
 * @property int $id                          Уникальный идентификатор автомобиля.
 * @property string $car_number               Номер автомобиля.
 * @property int $vendor_storage_id           Идентификатор склада поставщика.
 * @property string|null $driver_telegram_chat_id Идентификатор чата водителя в Telegram.
 * @property string $created_at              Дата и время создания записи.
 * @property string $updated_at              Дата и время последнего обновления записи.
 */
class VendorStorageCar extends Model
{
    protected $fillable = [
        'car_number',
        'vendor_storage_id',
        'driver_telegram_chat_id'
    ];

    /**
     * Получаем склад поставщика, к которому привязана машина.
     *
     * @return BelongsTo
     */
    public function vendorStorage(): BelongsTo
    {
        return $this->belongsTo(VendorStorage::class, 'vendor_storage_id');
    }
}
