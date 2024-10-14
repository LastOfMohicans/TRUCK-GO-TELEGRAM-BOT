<?php
declare(strict_types=1);

namespace App\Telegram;

use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;

/**
 * Класс для создания бота под телеграмм.
 * Создаем его, так как Nutgram создает бота в singleton и чтобы создать доп бота, нужен другой класс.
 */
class TelegramVendorClient extends Nutgram
{
    public function __construct(string $token, ?Configuration $config = null)
    {
        parent::__construct($token, $config);
    }
}
