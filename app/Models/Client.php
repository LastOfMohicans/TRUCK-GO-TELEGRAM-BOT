<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Определяет сущность клиента.
 *
 * @property string $id                                         Уникальный идентификатор пользователя.
 * @property string $name                                       Имя пользователя.
 * @property string $telegram_chat_id                           Идентификатор чата в телеграмме. Неуникальный,
 *            то есть в разных ботах у одного юзера он одинаковый
 * @property string last_telegram_action                        Дата и время последней активности пользователя.
 *
 * @property string $created_at                                 Дата и время создания записи.
 * @property string $updated_at                                 Дата и время обновления записи.
 */
class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;


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

    protected $fillable = [];


    /**
     * Атрибуты, которые должны быть приведены к определённым типам.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Получает заказы, связанные с пользователем.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Получает жалобы, связанные с пользователем.
     *
     * @return HasMany
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
