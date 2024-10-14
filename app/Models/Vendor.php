<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Определяет сущность поставщика.
 *
 * @property string $id                                                Уникальный идентификатор поставщика.
 * @property string $inn                                               ИНН.
 * @property string $type                                              Тип юзера (ИП или ООО).
 * @property string $company_name                                      Название компании у юридического лица.
 * @property string $ogrn                                              Государственный регистрационный номер.
 * @property string $address                                           Адрес юридического лица.
 * @property string $kpp                                               Идентификатор лица от налоговой.
 * @property string $telegram_chat_id                                  Идентификатор чата в телеграмме. Неуникальный,
 *           то есть в разных ботах у одного юзера он одинаковый.
 * @property string $name                                              Имя поставщика.
 * @property string last_telegram_action                               Дата и время последней активности пользователя.
 *
 * @property string $created_at                                        Дата и время создания записи.
 * @property string $updated_at                                        Дата и время последнего обновления записи.
 * @property string $deleted_at                                        Дата и время удаления записи.
 */
class Vendor extends Authenticatable
{
    use SoftDeletes, HasApiTokens, Notifiable, HasRoles;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Выставляем guard который будет использовать laravel-permission.
     * Для получения ролей и прав.
     *
     * @return string
     */
    public function guardName(): string
    {
        return 'vendor';
    }

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            /** Автоматически генерирует UUID при создании новой записи. */
            $model->{$model->getKeyName()} = (string)Str::uuid();
        });
    }

    protected $fillable = [
        'inn',
        'company_name',
        'ogrn',
        'address',
        'type',
        'kpp',
    ];

    /**
     * Получает активные склады, которые принадлежат поставщику.
     * Работает только у основного поставщика. Так как склады делаются на его идентификатор.
     *
     * @return HasMany
     */
    public function activeStorages(): HasMany
    {
        return $this->hasMany(VendorStorage::class)->where('vendor_storages.is_order_search_activated', true);
    }

    /**
     * Получаем все привязанные склады.
     *
     * @return BelongsToMany
     */
    public function vendorStorages(): BelongsToMany
    {
        return $this->belongsToMany(VendorStorage::class, 'vendor_vendor_storages', 'vendor_id', 'vendor_storage_id');
    }

    /**
     * Получаем управляющего.
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'owner_id');
    }

    /**
     * Получаем работников.
     *
     * @return HasMany
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Vendor::class, 'owner_id');
    }
}
