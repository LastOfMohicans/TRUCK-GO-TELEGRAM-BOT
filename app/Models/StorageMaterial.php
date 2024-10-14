<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Определяет сущность материала склада у поставщика.
 * То есть говорит нам, какие материалы имеет поставщик на складе.
 *
 * @property int $id                                                             Уникальный идентификатор материала.
 *           склада.
 * @property int $vendor_storage_id                                              Идентификатор склада поставщика.
 * @property int $material_id                                                    Идентификатор материала.
 * @property int $is_available                                                   Показывает, есть ли в наличии этот
 *           товар.
 * @property int $vendor_material_id                                             Идентификатор материала у поставщика.
 * @property int $cubic_meter_price                                              Цена за кубометр.
 * @property int $delivery_cost_per_cubic_meter_per_kilometer                    Цена доставки кубометра на 1 километр.
 *
 * @property string $created_at                                                  Дата и время создания записи.
 * @property string $updated_at                                                  Дата и время последнего обновления
 *           записи.
 * @property string $deleted_at                                                  Дата и время удаления записи.
 */
class StorageMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_storage_id',
        'material_id',
        'vendor_material_id',
        'is_available',
        'cubic_meter_price',
        'delivery_cost_per_cubic_meter_per_kilometer',
    ];

    /**
     * Получает склад, связанный с материалом.
     *
     * @return BelongsTo
     */
    public function vendorStorage(): BelongsTo
    {
        return $this->belongsTo(VendorStorage::class);
    }

    /**
     * Получает материал, к которому относится материал склада.
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Заполняет материалы на складе поставщика перед созданием из телеграмма.
     *
     * @param int $vendorStorageID
     * @param array $storageMaterialArr
     * @return StorageMaterial
     */
    public function fillStorageMaterial(int $vendorStorageID, array $storageMaterialArr): StorageMaterial
    {
        $storageMaterial = new StorageMaterial();
        $storageMaterial->material_id = $storageMaterialArr['material_id'];
        $storageMaterial->vendor_material_id = $storageMaterialArr['vendor_material_id'];
        $storageMaterial->vendor_storage_id = $vendorStorageID;
        $storageMaterial->is_available = $storageMaterialArr['is_available'];
        $storageMaterial->cubic_meter_price = $storageMaterialArr['price'];
        $storageMaterial->delivery_cost_per_cubic_meter_per_kilometer = $storageMaterialArr['delivery_price'];

        return $storageMaterial;
    }
}
