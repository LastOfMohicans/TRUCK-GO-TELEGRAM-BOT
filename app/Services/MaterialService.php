<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Material;
use App\Repositories\Material\MaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialService
{
    protected MaterialRepositoryInterface $materialRepository;

    public function __construct(MaterialRepositoryInterface $materialRepository)
    {
        $this->materialRepository = $materialRepository;
    }

    /**
     * Получить все активные материалы.
     *
     * @return Collection
     */
    function getActiveMaterials(): Collection
    {
        return $this->materialRepository->getActive();
    }

    /**
     * Получить материал с обязательными вопросами.
     *
     * @param string $materialID
     * @return Material|null
     */
    function getMaterialWithRequiredQuestionsByID(string $materialID): ?Material
    {
        return $this->materialRepository->getWithRequiredQuestionsByID($materialID);
    }

    /**
     * Получить материал по идентификатору.
     *
     * @param int $materialID
     * @return Material|null
     */
    function firstMaterial(int $materialID): ?Material
    {
        return $this->materialRepository->first($materialID);
    }

    /**
     * Получить материал по идентификатору каталога.
     *
     * @param int $catalogID
     * @return Material|null
     */
    function firstMaterialByCatalogID(int $catalogID): ?Material
    {
        return $this->materialRepository->firstByCatalogID($catalogID);
    }

}
