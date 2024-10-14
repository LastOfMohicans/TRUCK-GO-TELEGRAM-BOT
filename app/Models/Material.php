<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Определяет сущность материала.
 * Материалы являются основной сущностью, которую выбирает клиент при создании заказа.
 *
 * @property int $id                                     Уникальный идентификатор материала.
 * @property string $name                                Название материала.
 * @property bool $is_active                             Статус активности материала (активен или нет).
 * @property string $fraction                            Фракция. Используется для определения под типа материала.
 * @property string $type                                Тип. Используется для определения под типа материала.
 * @property int $catalog_id                             Идентификатор каталога которому принадлежит материал.
 * @property string $full_name                           Полное название материала с типами и тд.
 *
 * @property string $created_at                          Дата и время создания записи.
 * @property string $updated_at                          Дата и время последнего обновления записи.
 */
class Material extends Model
{
    use HasFactory;

    /**
     * Получает заказы, связанные с материалом.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Получает вопросы, связанные с материалом.
     *
     * @return HasMany
     */
    public function materialQuestions(): HasMany
    {
        return $this->hasMany(MaterialQuestion::class);
    }

    /**
     * Получает обязательные вопросы, связанные с материалом.
     * Отсортированные по активности.
     *
     * @return HasMany
     */
    public function requiredActiveMaterialQuestionsOrderByOrder(): HasMany
    {
        return $this->hasMany(MaterialQuestion::class)
            ->where('material_questions.is_active', true)
            ->where('material_questions.required', true)
            ->orderBy('material_questions.order');
    }
}
