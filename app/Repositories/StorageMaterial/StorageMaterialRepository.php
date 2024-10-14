<?php
declare(strict_types=1);

namespace App\Repositories\StorageMaterial;


use App\Models\StorageMaterial;
use Illuminate\Support\Collection;
use Throwable;

class StorageMaterialRepository implements StorageMaterialRepositoryInterface
{

    /**
     * @param StorageMaterial $storageMaterial
     * @return StorageMaterial
     * @throws Throwable
     */
    function create(StorageMaterial $storageMaterial): StorageMaterial
    {
        $storageMaterial->saveOrFail();

        return $storageMaterial;
    }

    function updateOrCreate(StorageMaterial $storageMaterial): StorageMaterial
    {
        return StorageMaterial::updateOrCreate(
            ['material_id' => $storageMaterial->material_id, 'vendor_storage_id' => $storageMaterial->vendor_storage_id],
            $storageMaterial->toArray(),
        );
    }

    /**
     * @param int $vendorStorageID
     * @return Collection
     */
    function getByVendorStorageID(int $vendorStorageID): Collection
    {
        return StorageMaterial::where('vendor_storage_id', $vendorStorageID)->get();
    }

    /**
     * @param int $materialID
     * @param int $vendorStorageID
     * @return StorageMaterial|null
     */
    function firstByID(int $materialID, int $vendorStorageID): ?StorageMaterial
    {
        return StorageMaterial::where('material_id', $materialID)
            ->where('vendor_storage_id', $vendorStorageID)
            ->first();
    }
}
