<?php

namespace App\Console\Commands;

use App\Telegram\TelegramDriverClient;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class StartTelegramDriverBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:telegram:polling-driver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускаем телеграм бот водителя в polling режиме. Использовать для тестирования при разработке';

    /**
     * Execute the console command.
     */
    public function handle(Nutgram $bot, TelegramDriverClient $driverBot)
    {
        $driverBot->run();
    }
}
