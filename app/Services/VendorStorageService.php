<?php
declare(strict_types=1);

namespace App\Services;

use App\Clients\Address;
use App\Contracts\GeocodingInterface;
use App\Contracts\ReverseGeocodingInterface;
use App\Contracts\RouteInterface;
use App\Enums\OrderHistoryChanger;
use App\Enums\Roles;
use App\Exceptions\AddressNotFoundException;
use App\Exceptions\EmptyAddressException;
use App\Exceptions\FailedToCreateOrderRequestException;
use App\Exceptions\FailedToUpdateVendorStorages;
use App\Jobs\NotifyVendorAboutAvailableOrderInTelegram;
use App\Models\OrderRequest;
use App\Models\OrderRequestHistory;
use App\Models\StorageMaterial;
use App\Models\Vendor;
use App\Models\VendorStorage;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderRequest\OrderRequestRepositoryInterface;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepositoryInterface;
use App\Repositories\StorageMaterial\StorageMaterialRepositoryInterface;
use App\Repositories\Vendor\VendorRepositoryInterface;
use App\Repositories\VendorStorage\VendorStorageRepositoryInterface;
use App\Repositories\VendorVendorStorage\VendorVendorStorageRepositoryInterface;
use App\StateMachines\OrderRequestStatusStateMachine;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;


class VendorStorageService
{
    const int MINIMUM_DISTANCE = 1; // Минимальная дистанция доставки 1 километр.
    const float MINIMUM_DISTANCE_FLOAT = 1.0; // Минимальная дистанция доставки 1 километр.
    protected GeocodingInterface $geoClient;
    protected StorageMaterialRepositoryInterface $storageMaterialRepository;
    protected VendorStorageRepositoryInterface $vendorStorageRepository;
    protected OrderRepositoryInterface $orderRepository;
    protected VendorRepositoryInterface $vendorRepository;
    protected OrderRequestRepositoryInterface $orderRequestRepository;
    protected OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository;
    protected ReverseGeocodingInterface $reverseGeoClient;
    protected RouteInterface $routeClient;
    protected VendorVendorStorageRepositoryInterface $vendorVendorStorageRepository;

    public function __construct(
        GeocodingInterface                     $geoClient,
        StorageMaterialRepositoryInterface     $storageMaterialRepository,
        VendorStorageRepositoryInterface       $vendorStorageRepository,
        OrderRepositoryInterface               $orderRepository,
        VendorRepositoryInterface              $vendorRepository,
        OrderRequestRepositoryInterface        $orderRequestRepository,
        OrderRequestHistoryRepositoryInterface $orderRequestHistoryRepository,
        ReverseGeocodingInterface              $reverseGeoClient,
        RouteInterface                         $routeClient,
        VendorVendorStorageRepositoryInterface $vendorVendorStorageRepository,
    )
    {
        $this->geoClient = $geoClient;
        $this->storageMaterialRepository = $storageMaterialRepository;
        $this->vendorStorageRepository = $vendorStorageRepository;
        $this->orderRepository = $orderRepository;
        $this->vendorRepository = $vendorRepository;
        $this->orderRequestRepository = $orderRequestRepository;
        $this->orderRequestHistoryRepository = $orderRequestHistoryRepository;
        $this->reverseGeoClient = $reverseGeoClient;
        $this->routeClient = $routeClient;
        $this->vendorVendorStorageRepository = $vendorVendorStorageRepository;
    }

    function getStoragesWithMaterials(string $vendorID): EloquentCollection
    {
        return VendorStorage::with(['materials', 'materials.material'])
            ->where('vendor_id', $vendorID)
            ->get();
    }


    /**
     * Обновляем склад и материалы вендора.
     *
     * @param string $vendorID
     * @param Collection $data
     * @return Collection|null
     * @throws Exception
     */
    function updateVendorStorages(string $vendorID, Collection $data): ?Collection
    {
        DB::beginTransaction();
        try {
            if (isset($data['storages_with_materials_to_create'])) {
                $resp = $this->createStorageMaterials($vendorID, $data['storages_with_materials_to_create']);
                if (isset($resp['errors'])) {
                    DB::rollBack();
                    return $resp;
                }
            }

            if (isset($data['storages_with_materials_to_edit'])) {
                $resp = $this->updateStorageMaterials($vendorID, $data['storages_with_materials_to_edit']);
                if (isset($resp['errors'])) {
                    DB::rollBack();
                    return $resp;
                }
            }

            if (isset($data['storages_with_materials_to_delete'])) {
                if (!$this->forceDeleteStoragesWithMaterials($vendorID, $data['storage_ids_to_remove'])) {
                    throw new FailedToUpdateVendorStorages();
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            throw new Exception('error updating vendor storages: ' . $e->getMessage());
        }

        return null;
    }


    /**
     * @param string $vendorID
     * @param array $storagesWithMaterialsToCreate
     * @return Collection|null
     * @throws Throwable
     */
    function createStorageMaterials(string $vendorID, array $storagesWithMaterialsToCreate): ?Collection
    {
        $storagesWithMaterials = $this->makeStorageWithMaterials($storagesWithMaterialsToCreate);
        if ($storagesWithMaterials->get('errors')) {
            return $storagesWithMaterials;
        }

        foreach ($storagesWithMaterials['data'] as $storageWithMaterials) {
            $vendorStorage = $this->fillVendorStorage($vendorID, $storageWithMaterials['address']);
            $this->vendorStorageRepository->create($vendorStorage);
            foreach ($storageWithMaterials['materials'] as $storageMaterialArr) {
                $storageMaterial = new StorageMaterial();
                $storageMaterial = $storageMaterial->fillStorageMaterial($vendorStorage->id, $storageMaterialArr);
                $this->storageMaterialRepository->create($storageMaterial);
            }
        }

        return null;
    }

    /**
     * Обновляем сущность склада.
     *
     * @param VendorStorage $vendorStorage
     * @return bool
     * @throws Throwable
     */
    function update(VendorStorage $vendorStorage): bool
    {
        return $this->vendorStorageRepository->update($vendorStorage);
    }

    /**
     * Обновляем точки и материалы.
     *
     * @param string $vendorID
     * @param $storagesWithMaterialsToEdit
     * @return void
     */
    function updateStorageMaterials(string $vendorID, $storagesWithMaterialsToEdit)
    {
        $storagesToEditCollection = new Collection($storagesWithMaterialsToEdit);
        $storageIDs = $storagesToEditCollection->pluck('storage_id')->unique()->toArray();

        $storages = VendorStorage::whereIn('id', $storageIDs)->where('vendor_id', $vendorID)->get();
        $storageIDToStorage = $storages->mapWithKeys(function ($item) {
            return [$item->id => $item];
        })->toArray();

        $storagesToUpdateAddress = [];
        foreach ($storagesWithMaterialsToEdit as $storageArr) {
            if (!isset($storageIDToStorage[$storageArr['storage_id']])) {
                continue; // кривой айдишник от юзера или фрод  TODO обсудить нужно ли тут возрвращать ошибку/уведомление юзеру
            }
            /** @var VendorStorage $storage */
            $storage = $storageIDToStorage[$storageArr['storage_id']];

            if ($storage['address'] != $storageArr['address']) {
                $storage['address'] = $storageArr['address'];
                $storagesToUpdateAddress[$storage['id']] = $storage; // делаем ключем айдишник, чтобы отсеять повторяющиеся элементы
            }
        }


        return DB::transaction(function () use ($vendorID, $storagesToUpdateAddress, $storagesWithMaterialsToEdit, $storageIDToStorage) {
            $errors = new Collection;
            foreach ($storagesToUpdateAddress as $storage) {
                try {
                    $address = $this->getAddressByCoordinates((float)$storage['latitude'],(float)$storage['longitude']);

                    $data=[
                        'latitude' => $storage['address']['latitude'],
                        'longitude' => $storage['address']['longitude'],
                        'postal_code' => $address->getPostalCode(),
                        'region' => $address->getRegion(),
                        'vendor_id' => $vendorID,
                        'address' => $address->getAddress(),
                    ];
                    $this->vendorStorageRepository->updateByID($storage['id'],$data);
                } catch (AddressNotFoundException $e) {
                    $errors[] = "Адреса {$storage['address']} не найден";
                } catch (EmptyAddressException $e) {
                    $errors[] = "Пустой адрес в точке номер {$storage['id']}";
                }
            }
            if (count($errors) > 0) {
                return $errors;
            }

            foreach ($storagesWithMaterialsToEdit as $materialArr) {
                if (!isset($storageIDToStorage[$materialArr['storage_id']])) {
                    continue;
                }
                /** @var VendorStorage $storage */
                $storage = $storageIDToStorage[$materialArr['storage_id']];
                $storageMaterial = new StorageMaterial([
                    'vendor_storage_id' => $storage['id'],
                    'material_id' => $materialArr['material_id'],
                    'vendor_material_id' => $materialArr['vendor_material_id'],
                    'cubic_meter_price' => $materialArr['price'],
                    'delivery_cost_per_cubic_meter_per_kilometer' => $materialArr['delivery_price'],
                    'is_available' => $materialArr['is_available'],
                ]);

                $this->storageMaterialRepository->updateOrCreate($storageMaterial);
            }

            return null;
        });
    }


    /**
     * Твердое удаление склада и материалов.
     *
     * @param $vendorID
     * @param $storageIDs
     * @return bool|null
     */
    function forceDeleteStoragesWithMaterials($vendorID, $storageIDs): ?bool
    {
        return $this->vendorStorageRepository->forceDeleteWithMaterials($vendorID, $storageIDs);
    }


    /**
     * Получение информации по адресам, при ошибке обработка продолжается.
     * Полученные данные записываются в коллекцию по ключу data.
     * Все ошибки записываем в коллекцию по ключу errors.
     *
     * @param Collection $addresses
     * @return Collection
     */
    function getAddressesByAddressesString(Collection $addresses): Collection
    {
        $response = new Collection();
        $errors = [];
        $data = [];
        foreach ($addresses as $i => $address) {
            try {
                $data[]['address'] = $this->getAddressByAddressString($address);
            } catch (AddressNotFoundException $e) {
                $errors[] = "Адреса {$address} не найден";
            } catch (EmptyAddressException $e) {
                $errors[] = "Пустой адрес в точке номер {$i}";
            }
        }

        $response->put('errors', $errors);
        $response->put('data', $data);

        return $response;
    }

    /**
     * Получение информации по адресу по строке адреса.
     * Строка адреса может быть не полной.
     *
     * @throws AddressNotFoundException
     * @throws EmptyAddressException
     */
    function getAddressByAddressString(string $addressStr): Address
    {
        if (empty($addressStr)) {
            throw new EmptyAddressException('empty address');
        }

        try {
            $address = $this->geoClient->getAddressByAddressString($addressStr);

            if (is_null($address)) {
                throw new AddressNotFoundException('failed to get address by address string: empty response');
            }
        } catch (GuzzleException $e) {
            report($e);
            throw new AddressNotFoundException('failed to get by address guzzle error: ' . $e->getMessage());
        }

        return $address;
    }

    /**
     * Получение информации по адресу по строке адреса.
     * Строка адреса может быть не полной.
     *
     * @throws AddressNotFoundException
     */
    public function getAddressByCoordinates(float $lat, float $lon): Address
    {
        try {
            $address = $this->reverseGeoClient->getAddressByCoordinates($lat, $lon);

            if (is_null($address)) {
                throw new AddressNotFoundException('failed to get address by coordinates: empty response');
            }
        } catch (GuzzleException $e) {
            report($e);
            throw new AddressNotFoundException('failed to get by coordinates guzzle error: ' . $e->getMessage());
        }
        return $address;
    }

    /**
     * Получение информации по адресам, при ошибке обработка продолжается.
     * Полученные данные записываются в коллекцию по ключу data.
     * Все ошибки записываем в коллекцию по ключу errors.
     *
     * @param Collection $coordinates
     * @return Collection
     */
    public function getAddressesByCoordinatesCollection(Collection $coordinates): Collection
    {
        $response = new Collection();
        $errors = [];
        $data = [];
        foreach ($coordinates as $i => $coordinate) {
            try {
                $data[]['address'] = $this->getAddressByCoordinates($coordinate['latitude'], $coordinate['longitude']);
            } catch (AddressNotFoundException $e) {
                $errors[] = "Адрес по координатам ({$coordinate['latitude']}, {$coordinate['longitude']}) не найден";
            } catch (Exception $e) {
                $errors[] = "Ошибка при обработке координат ({$coordinate['latitude']}, {$coordinate['longitude']}) в точке номер {$i}";
            }
        }

        $response->put('errors', $errors);
        $response->put('data', $data);

        return $response;
    }

    /**
     * Получаем все склады, которые могут принимать заказы, с доступными материалами.
     *
     * @return void
     * @throws FailedToCreateOrderRequestException
     */
    public function RunAlgorithmToFindVendorsForOrders(): void
    {
        $storagesWithMatLazyCollection = $this->vendorStorageRepository->getActiveWithAvailableMaterialsByLazyChunk();
        $storagesWithMatLazyCollection->each(function ($lazyCollection) {
            foreach ($lazyCollection as $storageWithMat) {
                /** @var VendorStorage $storageWithMat */

                $materialIDs = [];
                /** @var StorageMaterial $material */
                foreach ($storageWithMat->materials as $storageMaterial) {
                    $materialIDs[] = $storageMaterial->material_id;
                }

                $orderIDsAndDistanceCollection = $this->orderRepository->getActiveIDsInRadius(
                    $storageWithMat->latitude,
                    $storageWithMat->longitude,
                    $storageWithMat->max_delivery_radius,
                    $materialIDs,
                );

                if ($orderIDsAndDistanceCollection->isEmpty()) {
                    continue;
                }

                $orderIDs = [];
                $orderIDToDistance = [];
                foreach ($orderIDsAndDistanceCollection->toArray() as $orderIDAndDistance) {
                    $route = $this->routeClient->getRoute(
                        $storageWithMat->latitude,
                        $storageWithMat->longitude,
                        $orderIDAndDistance['latitude'],
                        $orderIDAndDistance['longitude'],
                    );

                    $distance = $route->getDistanceKm();
                    $orderID = $orderIDAndDistance['order_id'];
                    if ($distance < self::MINIMUM_DISTANCE) {
                        $distance = self::MINIMUM_DISTANCE_FLOAT; // Минимальная дистанция 1 километр.
                    }

                    $orderRequest = new OrderRequest();
                    $orderRequest->delivery_duration_minutes = $route->getTimeInMinutes();
                    $orderRequest->order_id = $orderID;
                    $orderRequest->vendor_id = $storageWithMat->vendor_id;
                    $orderRequest->status = OrderRequestStatusStateMachine::CREATED;
                    $orderRequest->distance = $distance;
                    $orderRequest->vendor_storage_id = $storageWithMat->id;

                    DB::beginTransaction();
                    try {
                        $this->orderRequestRepository->create($orderRequest);
                        $orderIDs[] = $orderID;
                        $orderIDToDistance[$orderID] = $distance;

                        $orderRequestHistory = new OrderRequestHistory();
                        $orderRequestHistory->status = $orderRequest->status;
                        $orderRequestHistory->order_request_id = $orderRequest->id;
                        $orderRequestHistory->changed_by = OrderHistoryChanger::System->value;

                        $this->orderRequestHistoryRepository->create($orderRequestHistory);
                        DB::commit();
                    } catch (Throwable $e) {
                        DB::rollBack();
                        report($e);
                        throw new FailedToCreateOrderRequestException();
                    }
                }

                if (count($orderIDs) == 0) {
                    continue;
                }

                $vendor = $this->vendorRepository->getByID($storageWithMat->vendor_id);
                if (!$vendor) {
                    Log::error("failed to find vendor when storage exists: vendor_id={vendor_id} vendor_storage_id={vendor_storage_id}", [
                        'vendor_id' => $storageWithMat->vendor_id,
                        'vendor_storage_id' => $storageWithMat->id,
                    ]);
                    continue;
                }

                NotifyVendorAboutAvailableOrderInTelegram::dispatch($orderIDs, $vendor->telegram_chat_id, $orderIDToDistance, $storageWithMat->id);
            }
        });
    }

    /**
     *
     *
     * @param array $storagesWithMaterialsToCreate
     * @return Collection
     */
    protected function makeStorageWithMaterials(array $storagesWithMaterialsToCreate): Collection
    {
        $storagesCollection = new Collection($storagesWithMaterialsToCreate);
        $addresses = $storagesCollection->unique('address')->pluck('address');
        $handledAddressesData = $this->getAddressesByCoordinatesCollection($addresses);

        if ($handledAddressesData->get('errors')) {
            return $handledAddressesData;
        }

        $handledAddresses = $handledAddressesData->get('data');
        // Здесь мы делаем масив точек с материалами
        // берем из первого товара в списке адрес
        // делаем этот адрес основным
        // дальше все товары без адреса групируются в материалы к этому адресу
        // как только встречаем в товаре новый адрес, делаем этот адрес основным и создаем новую точку
        // на выходе получается [['address' => 'addres1', 'materials' => [материалы]], ['address' => 'addres2', 'materials' => [материалы]]]
        $index = 0;

        foreach ($handledAddresses as $addressIndex => $addressArr) {
            $materials = [];
            $address = $storagesWithMaterialsToCreate[$index]['address'];
            for (; $index < count($storagesWithMaterialsToCreate); $index++) {
                if ($storagesWithMaterialsToCreate[$index]['address'] != $address) {
                    break;
                }

                $materials[] = $storagesWithMaterialsToCreate[$index];
            }

            $handledAddresses[$addressIndex]['materials'] = $materials;
        }

        $handledAddressesCollection = new Collection();
        $handledAddressesCollection->put('data', $handledAddresses);

        return $handledAddressesCollection;
    }


    /**
     * @param string $vendorID
     * @param Address $address
     * @return VendorStorage
     */
    protected function fillVendorStorage(string $vendorID, Address $address): VendorStorage
    {
        $vendorStorage = new VendorStorage();

        $vendorStorage->latitude = $address->getLatitude();
        $vendorStorage->longitude = $address->getLongitude();
        $vendorStorage->region = $address->getRegion();
        $vendorStorage->postal_code = $address->getPostalCode();
        $vendorStorage->address = $address->getAddress();
        $vendorStorage->vendor_id = $vendorID;
        $vendorStorage->is_order_search_activated = true;

        return $vendorStorage;
    }

    /**
     * Получаем все неактивные склады заказчика.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInactiveVendorStoragesPaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        /** @var Vendor $vendor */
        $vendor = Auth::user();
        if ($vendor->hasRole(Roles::Vendor)) {
            return $this->vendorStorageRepository->getInactiveByVendorIDPaginate($vendorID, $page, $perPage);
        }
        $availableVendorStorageIDs = $this->getAvailableVendorStorageIDs($vendorID);

        return $this->vendorStorageRepository->getInactivePaginate($availableVendorStorageIDs, $page, $perPage);
    }

    /**
     * Получаем все активные склады заказчика.
     *
     * @param string $vendorID
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveVendorStoragePaginate(string $vendorID, int $page, int $perPage = 5): LengthAwarePaginator
    {
        /** @var Vendor $vendor */
        $vendor = Auth::user();
        if ($vendor->hasRole(Roles::Vendor)) {
            return $this->vendorStorageRepository->getActiveByVendorIDPaginate($vendorID, $page, $perPage);
        }

        $availableVendorStorageIDs = $this->getAvailableVendorStorageIDs($vendorID);

        return $this->vendorStorageRepository->getActivePaginate($availableVendorStorageIDs, $page, $perPage);
    }


    /**
     * Получаем идентификаторы всех доступных поставщику складов.
     *
     * @param string $vendorID
     * @return array
     */
    public function getAvailableVendorStorageIDs(string $vendorID): array
    {
        $availableVendorStorages = $this->vendorVendorStorageRepository->getStorages($vendorID);
        return $availableVendorStorages->pluck('vendor_storage_id')->toArray();
    }

    /**
     * Включаем склады для поиска заказов.
     *
     * @param string $vendorID
     * @param array $activeStorages
     * @return bool
     */
    public function activeVendorStoragesOrderSearch(string $vendorID, array $activeStorages): bool
    {
        return $this->vendorStorageRepository->changeIsOrderSearchActivatedOnTrue($vendorID, $activeStorages);
    }

    /**
     * Выключаем склады для поиска заказов.
     *
     * @param string $vendorID
     * @param array $deactivateStorages
     * @return bool
     */
    public function deactivateVendorStoragesOrderSearch(string $vendorID, array $deactivateStorages): bool
    {
        return $this->vendorStorageRepository->changeIsOrderSearchActivatedOnFalse($vendorID, $deactivateStorages);
    }

    /**
     * Получаем склад поставщика.
     *
     * @param string $vendorID
     * @param integer $storageID
     * @return VendorStorage|null
     */
    public function firstVendorStorage(string $vendorID, int $storageID): ?VendorStorage
    {
        return $this->vendorStorageRepository->first($vendorID, $storageID);
    }

    /**
     * Получаем активный склад поставщика.
     *
     * @param string $vendorID
     * @return VendorStorage|null
     */
    public function firstActiveVendorStorage(string $vendorID): ?VendorStorage
    {
        $activeVendorStorage = $this->vendorStorageRepository->firstActive($vendorID);
        return $activeVendorStorage;
    }

    /**
     * Получаем идентификаторы всех складов.
     *
     * @param string $vendorID
     * @return Collection
     */
    public function getStorageIDs(string $vendorID): Collection
    {
        return $this->vendorStorageRepository->getStorageIDs($vendorID);
    }

}
