<?php
declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Inline\ClientMenu;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class CallClientMenuCommand extends Command
{
    protected string $command = 'client_menu';

    protected ?string $description = 'Меню клиента';

    public function handle(Nutgram $bot): void
    {
        ClientMenu::begin($bot);
    }
}
