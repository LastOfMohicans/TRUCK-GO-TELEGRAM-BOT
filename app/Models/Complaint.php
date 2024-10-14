<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Определяет сущность жалобы на какое-либо происшествие, которую может отправить любой пользователь.
 *
 * @property string $id                                        Уникальный идентификатор жалобы.
 * @property string $complaint                                 Текст жалобы.
 * @property string $client_id                                 Идентификатор клиента, отправившего жалобу.
 * @property string $vendor_id                                 Идентификатор поставщика, отправившего жалобу.
 * @property string $done                                      Идентификатор, что жалоба обработана.
 * @property string $complaint_reason_id                       Идентификатор причины и места жалобы.
 * @property string $severity                                  Критичность жалобы.
 *
 * @property string $created_at                                Дата и время создания жалобы.
 * @property string $updated_at                                Дата и время последнего обновления жалобы.
 * @property string $deleted_at                                Дата и время удаления жалобы.
 */
class Complaint extends Model
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
        'client_id' => 'string',
        'vendor_id' => 'string',
    ];

    /**
     * Получает клиента, отправившего жалобу.
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Получает поставщика, отправившего жалобу.
     *
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
