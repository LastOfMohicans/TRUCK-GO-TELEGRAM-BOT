<?php
declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidOrderRequestStatusException;
use App\StateMachines\OrderStatusStateMachine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Определяет сущность заказа, который создает клиент.
 *
 * @property string $id                          Уникальный идентификатор заказа.
 * @property int $material_id                    Идентификатор материала, связанного с заказом.
 * @property bool $is_activated                  Показывает, активен ли заказ для подбора поставщика.
 * @property bool $is_finished                   Показывает, завершил ли клиент создание заказа.
 * @property string $client_id                   Идентификатор клиента, сделавшего заказ.
 * @property string $status                      Текущий статус заказа.
 * @property string $accepted_request_id         Идентификатор заявки, которая выполняет заказ.
 * @property string $quantity                    Количество заказанного материала в м3.
 * @property string $comment                     Комментарий пользователя к заказу.
 *
 *
 * @property string $created_at                  Дата и время создания заказа.
 * @property string $updated_at                  Дата и время последнего обновления заказа.
 * @property string $deleted_at                  Дата и время удаления заказа.
 */
class Order extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // создаем uuid ключ
            $model->{$model->getKeyName()} = (string)Str::uuid();
        });
    }

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
                    throw new InvalidOrderRequestStatusException("trying to get invalid status in order status=$value");
                }

                return $value;
            },
            set: function (string $value) {
                if (!OrderStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderRequestStatusException("trying to set invalid status in order status=$value");
                }

                return $value;
            },
        );
    }

    /**
     *
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function orderQuestionAnswers(): HasMany
    {
        return $this->HasMany(OrderQuestionAnswer::class);
    }

    /**
     * Получаем все отклики на заказ.
     *
     * @return HasMany
     */
    public function orderRequests(): HasMany
    {
        return $this->HasMany(OrderRequest::class);
    }

    public function acceptedRequest(): HasOne
    {
        // TODO тут видимо надо сделать чтоб мы по клюучу из $Order получали $order request
        return $this->hasOne(OrderRequest::class, 'accepted_request_id');
    }

    /**
     * Получаем доставку прикрепленную к заказу.
     *
     * @return HasOne
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Получаем историю перемещений заказа.
     *
     * @return HasMany
     */
    public function orderHistory(): HasMany
    {
        return $this->hasMany(OrderHistory::class);
    }

}
