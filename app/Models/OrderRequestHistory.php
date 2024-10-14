<?php
declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidOrderStatusException;
use App\StateMachines\OrderRequestStatusStateMachine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель истории отклика, используется для отслеживания изменений статусов отклика.
 * Содержит информацию о том, кто и когда изменял статус отклика.
 *
 * @property int $id                              Уникальный идентификатор записи истории.
 * @property int $order_request_id                Идентификатор отклика, к которому относится эта запись.
 * @property int $changed_by                      Идентификатор кем был изменен отклик.
 * @property string $status                       Статус отклика после изменения.
 *
 * @property string $created_at                   Дата и время создания записи истории.
 */
class OrderRequestHistory extends Model
{
    const UPDATED_AT = null; // Опускаем запись даты обновления при создании сущности, так как в таблице колонка updated_at отсутствует.

    protected $fillable = [
        'status',
        'order_request_id',
        'changed_by',
    ];

    /**
     * Проверяет, что статус имеет допустимое значение из enum.
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                if (!OrderRequestStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderStatusException("Attempting to get an invalid status in order request history: status=$value");
                }
                return $value;
            },
            set: function (string $value) {
                if (!OrderRequestStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderStatusException("Attempting to set an invalid status in order request history: status=$value");
                }
                return $value;
            },
        );
    }
}
