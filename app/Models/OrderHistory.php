<?php
declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidOrderRequestStatusException;
use App\StateMachines\OrderStatusStateMachine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Модель истории заказа, используется для отслеживания изменений статусов заказов.
 * Содержит информацию о том, кто и когда изменял статус заказа.
 *
 * @property int $id                              Уникальный идентификатор заказа.
 * @property int $order_id                        Идентификатор заказа, к которому относится эта запись.
 * @property int changed_by                       Идентификатор кем был изменен заказ.
 * @property int status                           Статус заказа после изменения.
 *
 * @property string $created_at                   Дата и время создания записи истории.
 */
class OrderHistory extends Model
{
    const UPDATED_AT = null; // Опускаем запись даты обновления при создании сущности, так как в таблице колонка updated_at отсутствует.

    protected $fillable = [
        "order_id",
        'changed_by',
        'status',
    ];

    /**
     * Проверяем что статус имеет тип из enum.
     * Автоматически вызывается при get/set свойства.
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                if (!OrderStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderRequestStatusException("trying to get invalid status in order history=$value");
                }

                return $value;
            },
            set: function (string $value) {
                if (!OrderStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderRequestStatusException("trying to set invalid status in order history=$value");
                }

                return $value;
            },
        );
    }

    /**
     * Получаем заказ для которого записана история.
     *
     * @return HasOne
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }
}
