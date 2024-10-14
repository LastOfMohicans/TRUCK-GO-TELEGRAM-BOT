<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialQuestionsService
{

    protected MaterialQuestionRepositoryInterface $materialQuestionRepository;

    public function __construct(MaterialQuestionRepositoryInterface $materialQuestionRepository)
    {
        $this->materialQuestionRepository = $materialQuestionRepository;
    }


    /**
     * Получение обязательных вопросов с ответами.
     *
     * @param string $materialID Айди материала.
     * @return Collection
     */
    function getRequiredQuestionsWithAnswers(string $materialID): Collection
    {
        return $this->materialQuestionRepository->getRequiredWithAnswers($materialID);
    }

    /**
     * Получение обязательных вопросов без ответов.
     *
     * @param string $materialID Айди материала.
     * @return Collection
     */
    function getRequiredQuestions(string $materialID): Collection
    {
        return $this->materialQuestionRepository->getRequired($materialID);
    }

    /**
     * Получение всех активных вопросов.
     *
     * @param string $materialID Айди материала.
     * @return Collection
     */
    function getActiveQuestions(string $materialID): Collection
    {
        return $this->materialQuestionRepository->getActive($materialID);
    }


}
