<?php
declare(strict_types=1);

namespace App\Repositories\VendorVendorStorage;

use App\Models\VendorVendorStorage;
use Illuminate\Database\Eloquent\Collection;

class VendorVendorStorageRepository implements VendorVendorStorageRepositoryInterface
{

    /**
     * Получаем все склады привязанные к поставщику.
     *
     * @param string $vendorID
     * @return Collection
     */
    public function getStorages(string $vendorID): Collection
    {
        return VendorVendorStorage::where('vendor_id', $vendorID)->get();
    }
}
