<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Address;

/**
 * Интерфейс получения данных об адресе, исходя из координат.
 */
interface ReverseGeocodingInterface
{
    /**
     * Получаем информации об адресе по координатам.
     *
     * @param float $lat
     * @param float $lon
     * @return Address|null
     */
    function getAddressByCoordinates(float $lat, float $lon): ?Address;
}
