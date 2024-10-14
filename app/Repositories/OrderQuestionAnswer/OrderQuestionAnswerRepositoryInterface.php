<?php
declare(strict_types=1);

namespace App\Repositories\OrderQuestionAnswer;

use App\Models\OrderQuestionAnswer;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * Управляет order_question_answer сущностью в БД.
 */
interface OrderQuestionAnswerRepositoryInterface
{

    /**
     * Создать order questions answers.
     *
     * @param OrderQuestionAnswer[] $orderQuestionAnswer
     * @return bool
     */
    function createMany(array $orderQuestionAnswer): bool;

    /**
     * Получаем ответы юзера на выбранный вопрос в заказе.
     *
     * @param string $orderID
     * @param int $questionID
     * @return array|Collection
     */
    function getAnswers(string $orderID, int $questionID): array|Collection;

    /**
     * Удаляем ответ, устанавливая ему deleted_at.
     *
     * @param string $orderID
     * @param int $questionID
     * @return bool
     */
    function softDeleteAnswers(string $orderID, int $questionID): bool;

    /**
     * Создаем ответ на вопрос юзера в заказе.
     *
     * @param OrderQuestionAnswer $orderQuestionAnswer
     * @return OrderQuestionAnswer
     * @throws Throwable
     */
    function create(OrderQuestionAnswer $orderQuestionAnswer): OrderQuestionAnswer;
}
