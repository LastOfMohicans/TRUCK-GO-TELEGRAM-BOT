<?php
declare(strict_types=1);

namespace App\Repositories\Material;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

class MaterialRepository implements MaterialRepositoryInterface
{
    /**
     * @return Collection
     */
    function getActive(): Collection
    {
        return Material::where('is_active', true)
            ->get();
    }

    /**
     * @param string $materialID
     * @return Material|null
     */
    function getWithRequiredQuestionsByID(string $materialID): ?Material
    {
        return Material::with('requiredActiveMaterialQuestionsOrderByOrder')
            ->where('is_active', true)
            ->where('id', $materialID)
            ->select(['id', 'name'])
            ->first();
    }

    /**
     * @param int $materialID
     * @return Material|null
     */
    function first(int $materialID): ?Material
    {
        return Material::where('id', $materialID)->first();
    }

    /**
     * Получаем материал по идентификатору каталога.
     *
     * @param int $catalogID
     * @return Material|null
     */
    function firstByCatalogID(int $catalogID): ?Material
    {
        return Material::where('catalog_id', $catalogID)->first();
    }
}
