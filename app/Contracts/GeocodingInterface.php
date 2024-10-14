<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Address;

/**
 * Интерфейс получения данных об координатах исходя из адреса.
 */
interface GeocodingInterface
{
    /**
     * Получение информации по адресу по строке адреса.
     * Строка адреса может быть не полной.
     *
     * @param $address
     * @return Address|null
     */
    function getAddressByAddressString($address): ?Address;
}
