<?php
declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Models\Catalog;
use Illuminate\Database\Eloquent\Collection;

class CatalogRepository implements CatalogRepositoryInterface
{

    /**
     * Получаем все корневые сущности.
     *
     * @return Collection
     */
    public function getRoots(): Collection
    {
        return Catalog::where('parent_id', null)->get();
    }

    /**
     * Получаем всех детей каталога.
     *
     * @param int $catalogID
     * @return Collection
     */
    public function getChildren(int $catalogID): Collection
    {
        return Catalog::where('parent_id', $catalogID)->get();
    }

    /**
     * Получаем каталог по идентификатору.
     *
     * @param int $catalogID
     * @return Catalog
     */
    public function first(int $catalogID): Catalog
    {
        return Catalog::where('id', $catalogID)->first();
    }
}
