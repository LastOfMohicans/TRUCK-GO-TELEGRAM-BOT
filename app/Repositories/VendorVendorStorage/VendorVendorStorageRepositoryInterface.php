<?php
declare(strict_types=1);

namespace App\Repositories\VendorVendorStorage;

use Illuminate\Database\Eloquent\Collection;

/**
 * Управляет vendor_vendor_storage сущностью в БД.
 */
interface VendorVendorStorageRepositoryInterface
{

    /**
     * Получаем все склады привязанные к поставщику.
     *
     * @param string $vendorID
     * @return Collection
     */
    public function getStorages(string $vendorID): Collection;
}
