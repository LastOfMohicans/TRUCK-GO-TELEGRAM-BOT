<?php
declare(strict_types=1);

namespace App\Clients\DaData;

use App\Clients\Address;
use App\Clients\Company;
use App\Contracts\GeocodingInterface;
use App\Contracts\INNInterface;
use App\Contracts\ReverseGeocodingInterface;
use Dadata\DadataClient;

/**
 * Клиент dadata для получения данных об адресах, компания.
 */
class DaData implements GeocodingInterface, ReverseGeocodingInterface, INNInterface
{

    // API https://github.com/hflabs/dadata-php
    private DadataClient $client;

    public function __construct(string $token, string $secret)
    {
        $this->client = new DadataClient($token, $secret);
    }

    /**
     * @param $address
     * @return Address|null
     */
    function getAddressByAddressString($address): ?Address
    {
        $data = $this->client->clean('address', $address);

        if (empty($data) || !isset($data['result'])) {
            return null;
        }

        return new Address(
            floatval($data['geo_lat']),
            floatval($data['geo_lon']),
            $data['result'],
            $data['region'],
            $data['postal_code'],
            $data['timezone']
        );
    }

    /**
     * @param float $lat
     * @param float $lon
     * @return Address|null
     */
    function getAddressByCoordinates(float $lat, float $lon): ?Address
    {
        $data = $this->client->geolocate('address', $lat, $lon, 100, 1);

        if (empty($data) || !isset($data[0])) {
            return null;
        }

        $address = $data[0]['value'];
        $data = $data[0]['data'];
        return new Address(
            floatval($data['geo_lat']),
            floatval($data['geo_lon']),
            $address,
            $data['region'],
            $data['postal_code'],
            $data['timezone']
        );
    }

    /**
     * @param string $inn
     * @return Company|null
     */
    public function getCompanyDataByINN(string $inn): ?Company
    {
        $data = $this->client->findById('party', $inn, 1, ['type' => 'LEGAL', "status" => ["ACTIVE"]]);

        if (empty($data) || !isset($data[0])) {
            return null;
        }

        return $this->getCompany($data);
    }

    /**
     * @param string $inn
     * @return Company|null
     */
    public function getIndividualDataByINN(string $inn): ?Company
    {
        $data = $this->client->findById('party', $inn, 1, ['type' => 'INDIVIDUAL', "status" => ["ACTIVE"]]);

        if (empty($data) || !isset($data[0])) {
            return null;
        }

        return $this->getCompany($data);
    }

    /**
     * @param array $data
     * @return Company|null
     */
    private function getCompany(array $data): ?Company
    {
        $data = $data[0];
        if (empty($data) || !isset($data['data']['inn'])) {
            return null;
        }

        $address = $data['data']['address']['value'];
        if (isset($data['data']['address']['unrestricted_value'])) {
            $address = $data['data']['address']['unrestricted_value'];
        }

        $inn = $data['data']['inn'];
        $name = $data['value'];
        $ogrn = $data['data']['ogrn'];

        $kpp = "";
        if (isset($data['data']['kpp'])) {
            $kpp = $data['data']['kpp'];
        }

        return new Company(
            $inn,
            $name,
            $ogrn,
            $address,
            $kpp
        );
    }
}
