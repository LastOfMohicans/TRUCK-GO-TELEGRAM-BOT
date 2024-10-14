<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Определяет сущность ответа на вопрос.
 * Есть только у вопросов с типом ответа "выбор ответа".
 *
 * @property int $id                          Уникальный идентификатор ответа.
 * @property int $material_question_id        Идентификатор связанного вопроса.
 * @property string $answer                   Ответ на вопрос, который мы покажем пользователю.
 * @property bool $is_active                  Статус активности ответа, чтобы он показывался при ответе на вопрос.
 * @property int $order                       Порядок отображения ответа.
 *
 * @property string $created_at               Дата и время создания записи.
 * @property string $deleted_at               Дата и время удаления записи.
 */
class MaterialQuestionAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'material_question_id',
        'answer',
        'is_active',
        'order',
    ];

    /**
     * Получает вопрос, на который отвечает ответ.
     *
     * @return BelongsTo
     */
    public function materialQuestion(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestion::class);
    }
}
