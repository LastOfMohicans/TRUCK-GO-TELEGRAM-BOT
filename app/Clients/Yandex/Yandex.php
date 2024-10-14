<?php
declare(strict_types=1);

namespace App\Clients\Yandex;

use App\Clients\Address;
use App\Contracts\GeocodingInterface;
use Illuminate\Support\Facades\Http;


/**
 * Клиент Яндекс Geocoder для получения данных об адресе, исходя из координат или об координатах исходя из адреса.
 */
class Yandex implements GeocodingInterface
{
    protected string $apiKey = '';

    /**
     * Ссылка для работы с запросом Геокодера.
     */
    public const LINK_GEOCODE_MAPS = 'https://geocode-maps.yandex.ru/1.x/';

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Получение данных об адресе, исходя из координат или об координатах исходя из адреса.
     * В качестве аргумента используется либо адрес, либо координаты.
     *
     * @param $address
     * @return Address|null
     */
    public function getAddressByAddressString($address): ?Address
    {
        $response = Http::get(self::LINK_GEOCODE_MAPS, [
            'apikey' => $this->apiKey,
            'geocode' => $address,
            'results' => 1,
            'sco' => 'latlong',
            'format' => 'json',
        ]);

        $geoData = $response->json();

        if (count($geoData['response']['GeoObjectCollection']) == 0) {
            return null;
        }

        $point = $geoData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point'];
        $coordinates = explode(" ", $point['pos']);
        $longitude = $coordinates[0];
        $latitude = $coordinates[1];
        $geoAddress = $geoData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address']['formatted'];

        $region = null;
        $geoAdministrativeArea = $geoData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['AddressDetails']['Country']['AdministrativeArea'];
        if (array_key_exists('AdministrativeAreaName', $geoAdministrativeArea)) {
            $region = $geoAdministrativeArea['AdministrativeAreaName'];
        }

        $postalCode = null;
        $geoAddressDetails = $geoData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address'];
        if (array_key_exists('postal_code', $geoAddressDetails)) {
            $postalCode = $geoAddressDetails['postal_code'];
        }

        return new Address(
            floatval($latitude),
            floatval($longitude),
            $geoAddress,
            $region,
            $postalCode,
            null
        );
    }
}
