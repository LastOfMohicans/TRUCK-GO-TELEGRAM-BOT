<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Определяет сущность жалобы на какое-либо происшествие, которую может отправить любой пользователь.
 *
 * @property string $id                         Уникальный идентификатор.
 * @property string $reason                     Почему произошла проблема.
 * @property string $place                      Где произошла проблема.
 *
 * @property string $created_at                 Дата и время создания.
 * @property string $updated_at                 Дата и время последнего обновления.
 */
class ComplaintReason extends Model
{
    use SoftDeletes;
}
