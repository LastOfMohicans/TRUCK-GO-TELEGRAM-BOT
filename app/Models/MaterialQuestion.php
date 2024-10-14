<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Определяет сущность вопроса о материале.
 *
 * @property int $id                             Уникальный идентификатор вопроса.
 * @property int $material_id                    Идентификатор материала.
 * @property string $question                    Текст вопроса, отображаемого для пользователя.
 * @property string $question_answer_type        Тип ответа на вопрос (например: текст, выбор).
 * @property bool $is_active                     Активирует вопрос, чтобы он задавался при создании заказа.
 * @property int $order                          Порядок отображения вопроса.
 * @property bool $required                      Показывает, является ли ответ на этот вопрос обязательным для создания
 *           заказа.
 * @property int $question_type_id               Идентификатор типа вопроса.
 *
 * @property string $created_at                  Дата и время создания записи.
 * @property string $updated_at                  Дата и время обновления записи.
 */
class MaterialQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'question',
        'question_answer_type',
        'is_active',
        'order',
        'required',
        'question_type_id',
    ];

    /**
     * Получает материал, связанный с вопросом.
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Получает активные варианты ответов на вопрос, отсортированные по порядку.
     *
     * @return HasMany
     */
    public function activeMaterialQuestionAnswers(): HasMany
    {
        return $this->hasMany(MaterialQuestionAnswer::class)
            ->where('material_question_answers.is_active', true)
            ->orderBy('material_question_answers.order');
    }
}
