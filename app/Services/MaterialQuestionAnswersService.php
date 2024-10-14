<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MaterialQuestionAnswer\MaterialQuestionAnswerRepositoryInterface;
use Illuminate\Support\Collection;

class MaterialQuestionAnswersService
{

    protected MaterialQuestionAnswerRepositoryInterface $materialQuestionAnswerRepository;

    public function __construct(MaterialQuestionAnswerRepositoryInterface $materialQuestionAnswerRepository)
    {
        $this->materialQuestionAnswerRepository = $materialQuestionAnswerRepository;
    }

    /**
     *  Получаем коллекцию ответов на вопрос по айди вопроса.
     *
     * @param int $questionID
     * @return Collection
     */
    function getMaterialQuestionAnswer(int $questionID): Collection
    {
        return $this->materialQuestionAnswerRepository->get($questionID);
    }

}
