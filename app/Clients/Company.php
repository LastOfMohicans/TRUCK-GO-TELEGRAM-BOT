<?php

namespace App\Clients;

/**
 * Сущность компании.
 *
 * @property string $inn                                                 ИНН поставщика.
 * @property string $name                                                Имя поставщика.
 * @property string $ogrn                                                ОГРН поставщика
 * @property string $address                                             Адрес поставщика.
 * @property null|string $kpp                                            КПП поставщика. Может быть пустым.
 */
class Company
{
    protected string $inn;
    protected string $name;
    protected string $ogrn;
    protected string $address;
    protected ?string $kpp;

    /**
     * @param string $inn
     * @param string $name
     * @param string $ogrn
     * @param string $address
     * @param string|null $kpp
     */
    public function __construct(
        string $inn,
        string $name,
        string $ogrn,
        string  $address,
        ?string $kpp,
    )
    {
        $this->inn = $inn;
        $this->name = $name;
        $this->ogrn = $ogrn;
        $this->address = $address;
        $this->kpp = $kpp;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInn(): string
    {
        return $this->inn;
    }

    /**
     * @param string $inn
     * @return void
     */
    public function setInn(string $inn): void
    {
        $this->inn = $inn;
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
     * @return string
     */
    public function getOgrn(): string
    {
        return $this->ogrn;
    }

    /**
     * @param string $ogrn
     * @return void
     */
    public function setOgrn(string $ogrn): void
    {
        $this->ogrn = $ogrn;
    }

    /**
     * @return string|null
     */
    public function getKpp(): ?string
    {
        return $this->kpp;
    }

    /**
     * @param string $kpp
     * @return void
     */
    public function setKpp(string $kpp): void
    {
        $this->kpp = $kpp;
    }
}
