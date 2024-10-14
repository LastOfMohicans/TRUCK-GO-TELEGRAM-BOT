<?php

namespace App\Telegram\Router;

use App\Telegram\Commands\CallVendorMenuCommand;
use App\Telegram\Commands\Test\CallRemoveVendorCommand;
use App\Telegram\Commands\Test\RunFindOrdersForVendorsCommand;
use App\Telegram\Inline\RegisterVendor;
use App\Telegram\Inline\TestInline;
use App\Telegram\Middleware\Telegram\AuthVendorIfExistsMiddleware;
use App\Telegram\Middleware\Telegram\CheckNotRegisteredMiddleware;
use App\Telegram\Middleware\Telegram\IsVendorMiddleware;
use App\Telegram\TelegramDriverClient;

class DriverRoutes
{
    /**
     * @param TelegramVendorClient $driverBot
     * @return void
     */
    public function registerDefaulRoutes(TelegramDriverClient $driverBot): void
    {
        
    }

    /**
     * Тестовая команда, вызывается в телеграмме /test.
     * При вызове callable: TestInline::class - мы вызываем у класса метод start.
     *
     * @param TelegramVendorClient $driverBot
     * @return void
     */
    public function testDriverCommand(TelegramDriverClient $driverBot): void
    {
        $driverBot->onCommand('test', callable: TestInline::class);
    }
}
