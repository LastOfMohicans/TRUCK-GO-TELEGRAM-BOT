<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Сущность категории, используется для определения материала.
 *
 * @property int $id                              Идентификатор.
 * @property string $name                         Название категории.
 * @property int $parent_id                       Идентификатор родительской категории.
 * @property int question                         Вопрос каталога, ответом на который являются дети каталога.
 *
 * @property string $created_at                   Дата и время создания записи.
 * @property string $updated_at                   Дата и время обновления записи.
 */
class Catalog extends Model
{
    use HasFactory;
}
