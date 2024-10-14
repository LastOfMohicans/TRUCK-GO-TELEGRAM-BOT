<?php
declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidOrderStatusException;
use App\StateMachines\OrderRequestStatusStateMachine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Определяет сущность отклика на заказ.
 *
 * @property string $id                               Уникальный идентификатор записи.
 * @property string $order_id                         Идентификатор заказа на который создана заявка.
 * @property string $vendor_id                        Идентификатор поставщика, который создал заявку.
 * @property string $status                           Текущий статус заявки.
 * @property float $distance                          Расстояние между складом и точкой доставки напрямую в км.
 * @property string $archived_at                      Время когда заказ перестал быть активным. То есть нельзя
 *           откликнуться или отказаться.
 * @property bool $shown                              Был ли показан этот отклик поставщику.
 * @property int $vendor_storage_id                   Идентификатор склада для которого сделан запрос.
 * @property float $discount                          Процент скидки на исполнения заказа.
 * @property bool $is_discounted                      Показывает предоставил ли поставщик скидку при запросе клиента.
 * @property string $delivery_window_start            Время с которого нужно ожидать доставку.
 * @property string $delivery_window_end              Время до которого нужно ожидать доставку.
 * @property int $material_price                      Посчитанная цена за материал.
 * @property int $delivery_price                      Посчитанная цена за доставку.
 * @property int $delivery_duration_minutes           Время доставки от склада до клиента в минутах.
 *
 * @property string $created_at                       Дата и время создания записи.
 * @property string $updated_at                       Дата и время обновления записи.
 * @property string $deleted_at                       Дата и время удаления записи.
 */
class OrderRequest extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Автоматически генерирует UUID при создании новой записи.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string)Str::uuid();
        });
    }

    protected $casts = [
        'distance' => 'float',
    ];

    /**
     * Проверяет, что статус имеет допустимое значение из enum.
     * Автоматически вызывается при получении/установке свойства.
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                if (!OrderRequestStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderStatusException("trying to get invalid status in order request status=$value");
                }
                return $value;
            },
            set: function (string $value) {
                if (!OrderRequestStatusStateMachine::isStatusExists($value)) {
                    throw new InvalidOrderStatusException("trying to set invalid status in order request status=$value");
                }
                return $value;
            },
        );
    }

    /**
     * Получает заказ, связанный с откликом.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Получает вендора, связанного с откликом.
     *
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Получает склад, связанный с откликом.
     *
     * @return BelongsTo
     */
    public function vendorStorage(): BelongsTo
    {
        return $this->belongsTo(vendorStorage::class);
    }

    /**
     * Получает всю историю отклика на заказ.
     *
     * @return HasMany
     */
    public function orderRequestHistories(): HasMany
    {
        return $this->hasMany(OrderRequestHistory::class);
    }
}
