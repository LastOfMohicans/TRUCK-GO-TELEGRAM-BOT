<?php
declare(strict_types=1);

namespace App\Repositories\MaterialQuestion;

use Illuminate\Database\Eloquent\Collection;

/**
 * Управляет material_question сущностью в БД.
 */
interface MaterialQuestionRepositoryInterface
{

    /**
     * Получение обязательных, активных вопросов с ответами.
     *
     * @param string $materialID Айди материала
     * @return Collection
     */
    function getRequiredWithAnswers(string $materialID): Collection;

    /**
     * Получение обязательных и активных вопросов.
     *
     * @param string $materialID Айди материала
     * @return Collection
     */
    function getRequired(string $materialID): Collection;

    /**
     * Получение всех активных вопросов.
     *
     * @param string $materialID Айди материала.
     * @return Collection
     */
    function getActive(string $materialID): Collection;

    /**
     * Получение всех активных вопросов.
     *
     * @param array $questionIDs Айди вопросов.
     * @return Collection
     */
    function getActiveByIDs(array $questionIDs): Collection;
}
