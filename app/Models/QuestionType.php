<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Определяет сущность типа вопроса.
 * Используется для задания общего типа вопроса, который может быть использован для множества материалов.
 * Это помогает вычленять общую информацию о заказе в алгоритмах поиска.
 *
 * @property int $id                                Уникальный идентификатор типа вопроса.
 * @property string $type                           Тип вопроса. То есть вопрос на который он отвечает.
 * @property string $description                    Описание типа вопроса.
 *
 * @property string $created_at                     Дата и время создания записи.
 * @property string $updated_at                     Дата и время последнего обновления записи.
 * @property string $deleted_at                     Дата и время удаления записи.
 */
class QuestionType extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'type',
        'description',
    ];

    /**
     * Получает материал, связанный с типом вопроса.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestion::class);
    }
}
