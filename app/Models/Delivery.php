<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Определяет сущность доставки, которая показывает, куда и ко скольки нужно доставить заказ.
 *
 * @property string $id                                             Уникальный идентификатор доставки.
 * @property float $latitude                                        Широта.
 * @property float $longitude                                       Долгота.
 * @property string $address                                        Адрес куда нужно доставить.
 * @property string $finish_time                                    Время, к которому заказ был доставлен.
 * @property string $order_id                                       Идентификатор заказа которому принадлежит доставка.
 * @property string wanted_delivery_window_start                    Время с которого клиент ожидает доставку.
 * @property string wanted_delivery_window_end                      Время до которого клиент ожидает доставку.
 *
 * @property string $created_at                                     Дата и время создания записи.
 * @property string $updated_at                                     Дата и время последнего обновления записи.
 */
class Delivery extends Model
{

    protected $fillable = [];


    /**
     * Получаем заказ, к которому относится доставка.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
