<?php
declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Inline\VendorMenu;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class CallVendorMenuCommand extends Command
{
    protected string $command = 'vendor_menu';

    protected ?string $description = 'Меню поставщика';

    public function handle(Nutgram $bot): void
    {
        VendorMenu::begin($bot);
    }
}
