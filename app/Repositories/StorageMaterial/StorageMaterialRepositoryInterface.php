<?php
declare(strict_types=1);

namespace App\Repositories\StorageMaterial;

use App\Models\StorageMaterial;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Управляет storage_material сущностью в БД.
 */
interface StorageMaterialRepositoryInterface
{


    /**
     * Создаем материал склада.
     *
     * @param StorageMaterial $storageMaterial
     * @return StorageMaterial
     * @throws Throwable
     */
    function create(StorageMaterial $storageMaterial): StorageMaterial;


    /**
     * Обновляем или создаем материал склада.
     *
     * @param StorageMaterial $storageMaterial
     * @return StorageMaterial
     */
    function updateOrCreate(StorageMaterial $storageMaterial): StorageMaterial;

    /**
     * Получаем материалы склада по идентификатору склада.
     *
     * @param int $vendorStorageID
     * @return Collection
     */
    function getByVendorStorageID(int $vendorStorageID): Collection;

    /**
     * Получаем материал склада.
     *
     * @param int $materialID
     * @param int $vendorStorageID
     * @return StorageMaterial|null
     */
    function firstByID(int $materialID, int $vendorStorageID): ?StorageMaterial;
}
