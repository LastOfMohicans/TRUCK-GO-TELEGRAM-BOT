<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Address;

/**
 * Мок Геокодинга.
 */
class GeocodingMock implements GeocodingInterface, ReverseGeocodingInterface
{

    protected float $latitude = 55.7276281;
    protected float $longitude = 37.5790876;
    protected string $address = "Москва";

    protected ?string $region;
    protected ?string $timezone;
    protected ?string $postalCode;

    /**
     * @param $address
     * @return Address|null
     */
    function getAddressByAddressString($address): ?Address
    {
        return new Address($this->latitude, $this->longitude, $address);
    }

    function getAddressByCoordinates(float $lat, float $lon): ?Address
    {
        return new Address($lat, $lon, $this->address);
    }
}
