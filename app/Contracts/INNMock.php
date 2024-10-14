<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Company;

/**
 * Мок получения данных о ИП и ООО.
 */
class INNMock implements INNInterface
{

    /**
     * @param string $inn
     * @return Company
     */
    public function getCompanyDataByINN(string $inn): Company
    {
        $name = 'test company name';
        $ogrn = 'test company ogrn';
        $address = 'test company address';
        $kpp = 'test company kpp';       

        return new Company(
            $inn,
            $name,
            $ogrn,
            $address,
            $kpp
        );        
    }

    /**
     * @param string $inn
     * @return Company
     */
    public function getIndividualDataByINN(string $inn): Company
    {
        $name = 'test individual name';
        $ogrn = 'test individual ogrn';
        $address = 'test individual address';
        $kpp = 'test individual kpp';  

        return new Company(
            $inn,
            $name,
            $ogrn,
            $address,
            $kpp
        );        
    }
}
