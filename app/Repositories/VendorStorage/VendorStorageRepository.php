<?php

declare(strict_types=1);

namespace App\Repositories\VendorStorage;


use App\Models\VendorStorage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Throwable;

class VendorStorageRepository implements VendorStorageRepositoryInterface
{
    /**
     * @param VendorStorage $vendorStorage
     * @return VendorStorage
     * @throws Throwable
     */
    function create(VendorStorage $vendorStorage): VendorStorage
    {
        $vendorStorage->saveOrFail();

        return $vendorStorage;
    }

    /**
     * @param $vendorID
     * @param $storageIDs
     * @return bool|null
     */
    function forceDeleteWithMaterials($vendorID, $storageIDs): ?bool
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->whereIn('id', $storageIDs)
            ->forceDelete();
    }

    /**
     * @return LazyCollection
     */
    function getActiveWithAvailableMaterialsByLazyChunk(): LazyCollection
    {
        return VendorStorage::has('materials')
            ->with('materials')
            ->where('is_order_search_activated', true)
            ->lazy(100);
    }

    /**
     * Функция меняет значение is_order_search_activated на true.
     *
     * @param string $vendorID
     * @param array $activeStorages
     * @return bool
     */
    public function changeIsOrderSearchActivatedOnTrue(string $vendorID, array $activeStorages): bool
    {
        return boolval(
            VendorStorage::where('vendor_id', $vendorID)
                ->whereIn('id', $activeStorages)
                ->update(['is_order_search_activated' => true])
        );
    }

    /**
     * Функция возвращает склад поставщика.
     *
     * @param string $vendorID
     * @param integer $storageID
     * @return VendorStorage|null
     */
    public function first(string $vendorID, int $storageID): ?VendorStorage
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->where('id', $storageID)
            ->first();
    }

    /**
     * @param VendorStorage $vendorStorage
     * @return bool
     * @throws Throwable
     */
    function update(VendorStorage $vendorStorage): bool
    {
        return $vendorStorage->saveOrFail();
    }


    /**
     * @param int $id
     * @param array $data
     * @return void
     */
    public function updateByID(int $id, array $data): void
    {
        VendorStorage::where('id', $id)->update($data);
    }

    /**
     * Функция меняет значение is_order_search_activated на false.
     *
     * @param string $vendorID
     * @param array $deactivateStorages
     * @return bool
     */
    public function changeIsOrderSearchActivatedOnFalse(string $vendorID, array $deactivateStorages): bool
    {
        return boolval(
            VendorStorage::where('vendor_id', $vendorID)
                ->whereIn('id', $deactivateStorages)
                ->update(['is_order_search_activated' => false])
        );
    }

    /**
     *  Функция возвращает активный склад поставшика.
     *
     * @param string $vendorID
     * @return VendorStorage|null
     */
    public function firstActive(string $vendorID): ?VendorStorage
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->where('is_order_search_activated', true)
            ->first();
    }

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = true
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveByVendorIDPaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->where('is_order_search_activated', true)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = true.
     *
     * @param array $vendorStorageIDs
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivePaginate(array $vendorStorageIDs, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return VendorStorage::whereIn('id', $vendorStorageIDs)
            ->where('is_order_search_activated', true)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = false.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInactiveByVendorIDPaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->where('is_order_search_activated', false)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Получаем склады, которые имеют статус is_order_search_activated = false.
     *
     * @param array $vendorStorageIDs
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInactivePaginate(array $vendorStorageIDs, int $page, int $perPage = 5): LengthAwarePaginator
    {
        return VendorStorage::whereIn('id', $vendorStorageIDs)
            ->where('is_order_search_activated', false)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Получаем идентификаторы всех складов.
     *
     * @param string $vendorID
     * @return Collection
     */
    public function getStorageIDs(string $vendorID): Collection
    {
        return VendorStorage::where('vendor_id', $vendorID)
            ->where('is_order_search_activated', true)
            ->pluck('id');
    }

}
