<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Определяет сущность ответа на вопрос по материалу, который мы выбрали в заказе.
 *
 * @property int $id                          Уникальный идентификатор записи.
 * @property int $material_question_id        Идентификатор связанного вопроса из таблицы вопросов.
 * @property int $material_question_answer_id Идентификатор связанного ответа из таблицы ответов (для вопросов с
 *           вариантами ответов).
 * @property string answer                    Текст ответа на вопрос (для вопросов с кастомным ответом).
 * @property string order_id                  Идентификатор заказа, к которому относится ответ на вопрос.
 *
 * @property string created_at                Дата и время создания записи.
 * @property string updated_at                Дата и время последнего обновления записи.
 * @property string deleted_at                Дата и время удаления записи.
 */
class OrderQuestionAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'answer',
    ];

    /**
     * Получаем заказ, к которому относится ответ.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Получаем вопрос, к которому относится ответ.
     *
     * @return BelongsTo
     */
    public function materialQuestion(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestion::class);
    }

    /**
     * Получаем вариант ответа, если вопрос с выбор вариантов.
     *
     * @return BelongsTo
     */
    public function materialQuestionAnswer(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestionAnswer::class);
    }
}
