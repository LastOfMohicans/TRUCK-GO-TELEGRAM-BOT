<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Repositories\Catalog\CatalogRepositoryInterface;
use Illuminate\Support\Collection;

class CatalogService
{
    protected CatalogRepositoryInterface $catalogRepository;

    public function __construct(CatalogRepositoryInterface $catalogRepository)
    {
        $this->catalogRepository = $catalogRepository;
    }

    /**
     * Получаем все корневые элементы каталога.
     *
     * @return Collection
     */
    public function getRootCatalogs(): Collection
    {
        return $this->catalogRepository->getRoots();
    }

    /**
     * Получаем всех детей каталога или null, если у него нет детей.
     *
     * @param int $catalogID
     * @return ?Collection
     */
    public function getCatalogChildren(int $catalogID): ?Collection
    {
        $children = $this->catalogRepository->getChildren($catalogID);
        if ($children->isEmpty()) {
            return null;
        }

        return $children;
    }

    /**
     * Получаем каталог по идентификатору.
     *
     * @param int $catalogID
     * @return ?Catalog
     */
    public function firstCatalog(int $catalogID): ?Catalog
    {
        return $this->catalogRepository->first($catalogID);
    }

}
