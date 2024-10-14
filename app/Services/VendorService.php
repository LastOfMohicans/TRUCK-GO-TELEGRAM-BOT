<?php
declare(strict_types=1);

namespace App\Services;

use App\Clients\Company;
use App\Contracts\INNInterface;
use App\Enums\Roles;
use App\Exceptions\CompanyNotFoundException;
use App\Exceptions\FailedToCreateVendorException;
use App\Exceptions\FailedUpdateVendorCompanyData;
use App\Exceptions\IndividualNotFoundException;
use App\Models\Vendor;
use App\Repositories\Client\ClientRepositoryInterface;
use App\Repositories\Vendor\VendorRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class VendorService
{
    protected INNInterface $innClient;
    protected VendorStorageService $vendorStorageService;
    protected VendorRepositoryInterface $vendorRepository;
    protected ClientRepositoryInterface $clientRepository;

    /**
     * Количество символов в ИНН поставщика.
     */
    const COMPANY_INN_SYMBOLS_COUNT = 10;
    const INDIVIDUAL_INN_SYMBOLS_COUNT = 12;

    public function __construct(
        INNInterface              $innClient,
        VendorStorageService      $vendorStorageService,
        VendorRepositoryInterface $vendorRepository,
        ClientRepositoryInterface $clientRepository,
    )
    {
        $this->innClient = $innClient;
        $this->vendorStorageService = $vendorStorageService;
        $this->vendorRepository = $vendorRepository;
        $this->clientRepository = $clientRepository;
    }

    /**
     * Получаем информацию о компании, заносим ее в модель поставщика и возвращаем модель.
     *
     * @param string $inn
     * @return Vendor
     * @throws CompanyNotFoundException
     */
    public function makeVendorFromCompanyData(string $inn): Vendor
    {
        $companyData = $this->innClient->getCompanyDataByINN($inn);
        if (!$companyData) {
            throw new CompanyNotFoundException('empty response from get company data by inn: ' . $inn);
        }

        return $this->makeVendor($companyData);
    }


    /**
     * Получаем информацию о ИП, заносим ее в модель поставщика и возвращаем модель.
     *
     * @param string $inn
     * @return Vendor
     * @throws IndividualNotFoundException
     */
    public function makeVendorFromIndividualData(string $inn): Vendor
    {
        $invData = $this->innClient->getIndividualDataByINN($inn);
        if (!$invData) {
            throw new IndividualNotFoundException('empty response from get individual data by inn: ' . $inn);
        }

        return $this->makeVendor($invData);
    }

    /**
     * Обновляем данные о поставщике.
     *
     * @param Vendor $vendor
     * @return void
     * @throws FailedUpdateVendorCompanyData
     */
    public function updateVendorCompanyData(Vendor $vendor): void
    {
        $inn = $vendor->inn;
        $quantity = mb_strlen($inn, 'utf8');

        if ($quantity == self::COMPANY_INN_SYMBOLS_COUNT) {
            $data = $this->innClient->getCompanyDataByINN($inn);
        } else if ($quantity == self::INDIVIDUAL_INN_SYMBOLS_COUNT) {
            $data = $this->innClient->getIndividualDataByINN($inn);
        }

        if (empty($data)) {
            return;
        }

        $vendor->inn = $data->getInn();
        $vendor->company_name = $data->getName();
        $vendor->ogrn = $data->getOgrn();
        $vendor->address = $data->getAddress();

        if ($data->getKpp() != null) {
            $vendor->kpp = $data->getKpp();
        }

        try {
            $this->vendorRepository->update($vendor);
        } catch (Throwable $e) {
            report($e);
            throw new FailedUpdateVendorCompanyData();
        }
    }

    /**
     * Проверяем существует ли переданный ИНН.
     *
     * @param string $inn
     * @return bool
     */
    public function isVendorINNExists(string $inn): bool
    {
        return $this->vendorRepository->isINNExists($inn);
    }

    /**
     * Создаем поставщика со складом и материалами.
     *
     * @param Vendor $vendor
     * @param array $storagesWithMaterialsToCreate
     * @return Vendor
     * @throws FailedToCreateVendorException
     */
    public function createVendorWithStoragesAndMaterials(Vendor $vendor, array $storagesWithMaterialsToCreate): Vendor
    {
        DB::beginTransaction();
        try {
            $vendor = $this->vendorRepository->create($vendor);

            $this->vendorStorageService->createStorageMaterials($vendor->id, $storagesWithMaterialsToCreate);

            $vendor->assignRole(Roles::Vendor, 'vendor');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            throw new FailedToCreateVendorException();
        }

        return $vendor;
    }

    /**
     * Получаем поставщика по идентификатору чата в телеграмм.
     *
     * @param string $telegramChatID
     * @return Vendor|null
     */
    public function getVendorByTelegramChatID(string $telegramChatID): ?Vendor
    {
        return $this->vendorRepository->getByTelegramChatID($telegramChatID);
    }

    /**
     * Получаем информацию о поставщике.
     *
     * @param Company $company
     * @return Vendor
     */
    protected function makeVendor(Company $company): Vendor
    {
        $inn = $company->getInn();
        $company_name = $company->getName();
        $ogrn = $company->getOgrn();
        $address = $company->getAddress();
        $kpp = "";
        if (!empty($company->getKpp())) {
            $kpp = $company->getKpp();
        }

        $vendor = new Vendor();

        $vendor->inn = $inn;
        $vendor->company_name = $company_name;
        $vendor->ogrn = $ogrn;
        $vendor->address = $address;
        $vendor->kpp = $kpp;

        return $vendor;
    }


    /**
     * Обновляет последнюю активность пользователя.
     *
     * @param string $vendorID
     * @return void
     * @throws Throwable
     */
    public function updateLastTelegramAction(string $vendorID): void
    {
        $nowTime = Carbon::now()->toDateTimeString();
        $this->vendorRepository->updateLastTelegramAction($vendorID, $nowTime);
    }
}
