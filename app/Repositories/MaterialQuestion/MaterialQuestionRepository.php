<?php
declare(strict_types=1);

namespace App\Repositories\MaterialQuestion;

use App\Models\MaterialQuestion;
use Illuminate\Database\Eloquent\Collection;

class MaterialQuestionRepository implements MaterialQuestionRepositoryInterface
{
    /**
     * @param string $materialID
     * @return Collection
     */
    function getRequiredWithAnswers(string $materialID): Collection
    {
        return MaterialQuestion::with('activeMaterialQuestionAnswers')
            ->where('material_questions.material_id', $materialID)
            ->where('material_questions.is_active', true)
            ->where('material_questions.required', true)
            ->orderBy('material_questions.order')
            ->get();
    }

    function getRequired(string $materialID): Collection
    {
        return MaterialQuestion::where('material_questions.material_id', $materialID)
            ->where('material_questions.is_active', true)
            ->where('material_questions.required', true)
            ->orderBy('material_questions.order')
            ->get();
    }

    function getActive(string $materialID): Collection
    {
        return MaterialQuestion::where('material_questions.material_id', $materialID)
            ->where('material_questions.is_active', true)
            ->orderBy('material_questions.order')
            ->get();
    }

    function getActiveByIDs(array $questionIDs): Collection
    {
        return MaterialQuestion::where('material_questions.is_active', '=', true)
            ->findMany($questionIDs);
    }
}
