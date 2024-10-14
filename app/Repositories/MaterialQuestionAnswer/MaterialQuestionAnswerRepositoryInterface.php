<?php
declare(strict_types=1);

namespace App\Repositories\MaterialQuestionAnswer;

use Illuminate\Support\Collection;

/**
 * Управляет material_question_answer сущностью в БД.
 */
interface MaterialQuestionAnswerRepositoryInterface
{

    /**
     * Получаем коллекцию ответов на вопрос по айди вопроса.
     *
     * @param int $questionID
     * @return Collection
     */
    function get(int $questionID): Collection;
}
