<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Company;

/**
 * Интерфейс для получения данных о физ и юр лице по ИНН.
 */
interface INNInterface
{
    /**
     * Получение данных по юр лицу.
     *
     * @param string $inn
     * @return Company|null
     */
    public function getCompanyDataByINN(string $inn): ?Company;

    /**
     * Получения данных по физ лицу.
     *
     * @param string $inn
     * @return Company|null
     */
    public function getIndividualDataByINN(string $inn): ?Company;


}
