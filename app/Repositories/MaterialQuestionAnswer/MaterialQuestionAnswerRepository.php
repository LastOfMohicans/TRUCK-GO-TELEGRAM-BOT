<?php
declare(strict_types=1);

namespace App\Repositories\MaterialQuestionAnswer;

use App\Models\MaterialQuestionAnswer;
use Illuminate\Support\Collection;

class MaterialQuestionAnswerRepository implements MaterialQuestionAnswerRepositoryInterface
{
    /**
     * @param int $questionID
     * @return Collection
     */
    function get(int $questionID): Collection
    {
        return MaterialQuestionAnswer::where('material_question_answers.material_question_id', $questionID)
            ->where('material_question_answers.is_active', '=', true)
            ->orderBy('material_question_answers.order')
            ->get();
    }
}
