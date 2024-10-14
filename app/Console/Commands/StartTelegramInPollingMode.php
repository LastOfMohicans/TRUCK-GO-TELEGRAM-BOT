<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Telegram\TelegramVendorClient;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class StartTelegramInPollingMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:telegram:polling-vendor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускаем телеграм бот поставщика в polling режиме. Использовать для тестирования при разработке';

    /**
     * Execute the console command.
     */
    public function handle(Nutgram $bot, TelegramVendorClient $vendorBot)
    {
        $vendorBot->run();
    }
}
