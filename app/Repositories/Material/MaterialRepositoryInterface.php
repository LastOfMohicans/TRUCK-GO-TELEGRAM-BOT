<?php
declare(strict_types=1);

namespace App\Repositories\Material;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

/**
 * Управляет material сущностью в БД.
 */
interface MaterialRepositoryInterface
{

    /**
     * Получить все активные материалы.
     *
     * @return Collection
     */
    function getActive(): Collection;

    /**
     * Получить материал с обязательными вопросами.
     *
     * @param string $materialID Идентификатор материала.
     * @return Material|null
     */
    function getWithRequiredQuestionsByID(string $materialID): ?Material;

    /**
     * Получаем материал по идентификатору.
     *
     * @param int $materialID
     * @return Material|null
     */
    function first(int $materialID): ?Material;

    /**
     * Получаем материал по идентификатору каталога.
     *
     * @param int $catalogID
     * @return Material|null
     */
    function firstByCatalogID(int $catalogID): ?Material;

}
