<?php
declare(strict_types=1);

namespace App\Clients;


/**
 * Сущность адреса.
 *
 * @property float $latitude                                                      Широта.
 * @property float $longitude                                                     Долгота.
 * @property string $region                                                       Регион.
 * @property null|string $timezone                                                Таимзона в формате UTC+6.
 * @property null|string $address                                                 Полный адрес.
 * @property null|string $postalCode                                              Почтовый индекс. Может быть пустым.
 */
class Address
{
    protected float $latitude;
    protected float $longitude;
    protected string $address;

    protected ?string $region;
    protected ?string $timezone;
    protected ?string $postalCode;

    /**
     * @param float $latitude
     * @param float $longitude
     * @param string $address
     * @param string|null $region
     * @param string|null $postalCode
     * @param string|null $timezone
     */
    public function __construct(
        float   $latitude,
        float   $longitude,
        string  $address,
        ?string $region = null,
        ?string $postalCode = null,
        ?string $timezone = null,
    )
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->region = $region;
        $this->postalCode = $postalCode;
        $this->address = $address;
        $this->timezone = $timezone;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     * @return void
     */
    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     * @return void
     */
    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @param string $region
     * @return void
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return void
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string|null $postalCode
     * @return void
     */
    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
