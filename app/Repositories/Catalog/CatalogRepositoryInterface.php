<?php
declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Models\Catalog;
use Illuminate\Database\Eloquent\Collection;

/**
 * Управляет catalog сущностью в БД.
 */
interface CatalogRepositoryInterface
{

    /**
     * Получаем все корневые сущности.
     *
     * @return Collection
     */
    public function getRoots(): Collection;

    /**
     * Получаем всех детей каталога.
     *
     * @param int $catalogID
     * @return Collection
     */
    public function getChildren(int $catalogID): Collection;

    /**
     * Получаем каталог по идентификатору.
     *
     * @param int $catalogID
     * @return Catalog
     */
    public function first(int $catalogID): Catalog;
}
