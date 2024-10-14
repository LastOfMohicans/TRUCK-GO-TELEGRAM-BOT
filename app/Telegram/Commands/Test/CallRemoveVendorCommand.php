<?php
declare(strict_types=1);

namespace App\Telegram\Commands\Test;

use App\Http\Controllers\TelegramTestController;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class CallRemoveVendorCommand extends Command
{
    protected string $command = 'remove_vendor';

    protected ?string $description = 'Удаляем поставщика и все связанные данные.';

    public function handle(Nutgram $bot, TelegramTestController $c): void
    {
        $c->removeVendorAndHisData();
        $bot->sendMessage("Данные успешно удалены");
    }
}
