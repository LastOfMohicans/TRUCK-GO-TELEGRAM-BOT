<?php
declare(strict_types=1);

namespace App\Telegram\Commands\Test;

use App\Http\Controllers\TelegramTestController;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class CallRemoveClientCommand extends Command
{
    protected string $command = 'remove_client';

    protected ?string $description = 'Удаляем клиента и все связанные данные.';

    public function handle(Nutgram $bot, TelegramTestController $c): void
    {
        $c->removeClientAndHisData();
        $bot->sendMessage("Данные успешно удалены");
    }
}
