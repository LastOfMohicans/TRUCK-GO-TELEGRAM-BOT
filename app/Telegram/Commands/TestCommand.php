<?php
declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Services\VendorStorageService;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class TestCommand extends Command
{
    protected string $command = 'test_command';

    protected ?string $description = 'Тестовая команда ';

    public function handle(Nutgram $bot, VendorStorageService $vendorStorageService): void
    {
        $bot->sendMessage("start test command");
    }
}
