<?php
declare(strict_types=1);

namespace App\Repositories\OrderQuestionAnswer;


use App\Models\OrderQuestionAnswer;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class OrderQuestionAnswerRepository implements OrderQuestionAnswerRepositoryInterface
{

    /**
     * @param OrderQuestionAnswer[] $orderQuestionAnswer
     * @return bool
     */
    function createMany(array $orderQuestionAnswer): bool
    {
        return OrderQuestionAnswer::insert($orderQuestionAnswer);
    }

    /**
     * @param string $orderID
     * @param int $questionID
     * @return array|Collection
     */
    function getAnswers(string $orderID, int $questionID): array|Collection
    {
        return OrderQuestionAnswer::where('order_id', $orderID)
            ->where('material_question_id', $questionID)
            ->get();
    }

    /**
     * @param string $orderID
     * @param int $questionID
     * @return bool
     */
    function softDeleteAnswers(string $orderID, int $questionID): bool
    {
        return OrderQuestionAnswer::where('order_id', $orderID)
            ->where('material_question_id', $questionID)
            ->delete();
    }

    /**
     * @param OrderQuestionAnswer $orderQuestionAnswer
     * @return OrderQuestionAnswer
     * @throws Throwable
     */
    function create(OrderQuestionAnswer $orderQuestionAnswer): OrderQuestionAnswer
    {
        $orderQuestionAnswer->saveOrFail();

        return $orderQuestionAnswer;
    }
}
