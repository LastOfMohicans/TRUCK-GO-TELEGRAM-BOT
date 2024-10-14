<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Определяет связь между поставщиками и складами.
 *
 * @property string $id                                              Идентификатор пользователя.
 * @property string $vendor_id                                       Идентификатор поставщика.
 * @property string $vendor_storage_id                               Идентификатор склада.
 *
 * @property string $created_at                                      Дата и время создания записи.
 * @property string $updated_at                                      Дата и время обновления записи.
 */
class VendorVendorStorage extends Model
{
    protected $table = 'vendor_vendor_storage';


    /**
     * Получаем связанного поставщика.
     *
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Получаем связанный склад.
     *
     * @return BelongsTo
     */
    public function vendorStorage(): BelongsTo
    {
        return $this->belongsTo(VendorStorage::class, 'vendor_storage_id');
    }
}
