<?php
declare(strict_types=1);

namespace App\Repositories\VendorStorage;

use App\Models\VendorStorage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Throwable;

/**
 * Управляет vendor_storage сущностью в БД.
 */
interface VendorStorageRepositoryInterface
{
    /**
     * Создаем склад поставщика.
     *
     * @param VendorStorage $vendorStorage
     * @return VendorStorage
     * @throws Throwable
     */
    function create(VendorStorage $vendorStorage): VendorStorage;

    /**
     * Твердое удаление склада, что так же удалит все его материалы.
     *
     * @param $vendorID
     * @param $storageIDs
     * @return bool|null
     */
    function forceDeleteWithMaterials($vendorID, $storageIDs): ?bool;

    /**
     * Получаем все склады, которые могут принимать заказы, с доступными материалами.
     *
     * @return LazyCollection
     */
    function getActiveWithAvailableMaterialsByLazyChunk(): LazyCollection;

    /**
     * Меняет значение is_order_search_activated на true.
     *
     * @param string $vendorID
     * @param array $activeStorages
     * @return bool
     */
    public function changeIsOrderSearchActivatedOnTrue(string $vendorID, array $activeStorages): bool;

    /**
     * Возвращает склад владельца.
     *
     * @param string $vendorID
     * @param integer $storageID
     * @return VendorStorage|null
     */
    public function first(string $vendorID, int $storageID): ?VendorStorage;

    /**
     * Обновляем сущность склада.
     *
     * @param VendorStorage $vendorStorage
     * @return bool
     * @throws Throwable
     * */
    function update(VendorStorage $vendorStorage): bool;

    /**
     * Обновляет сущность склада по ID.
     *
     * @param int $id
     * @param array $data
     * @return void
     */
    public function updateByID(int $id, array $data): void;

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = true.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveByVendorIDPaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = true.
     *
     * @param array $vendorStorageIDs
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivePaginate(array $vendorStorageIDs, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = false.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInactiveByVendorIDPaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Получаем все склады, которые имеют статус is_order_search_activated = false.
     *
     * @param array $vendorStorageIDs
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInactivePaginate(array $vendorStorageIDs, int $page, int $perPage = 5): LengthAwarePaginator;

    /**
     * Меняет значение is_order_search_activated на false.
     *
     * @param string $vendorID
     * @param array $deactivateStorages
     * @return bool
     */
    public function changeIsOrderSearchActivatedOnFalse(string $vendorID, array $deactivateStorages): bool;

    /**
     * Возвращает активный склад поставщика.
     *
     * @param string $vendorID
     * @return VendorStorage|null
     */
    public function firstActive(string $vendorID): ?VendorStorage;

    /**
     * Получаем идентификаторы всех складов.
     *
     * @param string $vendorID
     * @return Collection
     */
    public function getStorageIDs(string $vendorID): Collection;
}
