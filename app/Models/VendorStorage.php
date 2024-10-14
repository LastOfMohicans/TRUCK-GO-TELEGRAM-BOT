<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель VendorStorage
 *
 * Определяет сущность склада поставщика.
 *
 * @property int $id                                    Уникальный идентификатор склада поставщика.
 * @property string $latitude                           Широта.
 * @property string $longitude                          Долгота.
 * @property bool $is_order_search_activated            Активирован ли склад для поиска заказов.
 * @property string $region                             Регион.
 * @property string $postal_code                        Почтовый индекс.
 * @property string $address                            Адрес склада.
 * @property string $vendor_id                          Идентификатор поставщика.
 * @property int $max_delivery_radius                   Максимальный радиус доставки заказа со склада(км).
 *
 * @property string $created_at                         Дата и время создания записи.
 * @property string $updated_at                         Дата и время последнего обновления записи.
 * @property string $deleted_at                         Дата и время удаления записи.
 */
class VendorStorage extends Model
{
    use SoftDeletes;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $fillable = [
        'latitude',
        'longitude',
        'region',
        'postal_code',
        'address',
        'is_order_search_activated',
        'max_delivery_radius',
    ];

    /**
     * Получает поставщика, которому принадлежит склад.
     *
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Получает материалы, связанные со складом.
     *
     * @return HasMany
     */
    public function materials(): HasMany
    {
        return $this->hasMany(StorageMaterial::class);
    }

    /**
     *
     * Получает материалы которые доступны на складе.
     *
     * @return HasMany
     */
    public function getAvailableMaterials(): HasMany
    {
        return $this->hasMany(StorageMaterial::class);
    }

    /**
     *
     * Получаем все отклики связанные со складом.
     *
     * @return HasMany
     */
    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    /**
     * Получаем все машины, привязанные к этому складу.
     *
     * @return HasMany
     */
    public function vendorStorageCars(): HasMany
    {
        return $this->hasMany(VendorStorageCar::class, 'vendor_storage_id');
    }

    /**
     * Получаем всех привязанных поставщиков.
     *
     * @return BelongsToMany
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_vendor_storages', 'vendor_id', 'vendor_storage_id');
    }
}
