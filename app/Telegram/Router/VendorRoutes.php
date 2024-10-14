<?php

declare(strict_types=1);

namespace App\Telegram\Router;

use App\Telegram\Commands\CallVendorMenuCommand;
use App\Telegram\Commands\Test\CallRemoveVendorCommand;
use App\Telegram\Commands\Test\RunFindOrdersForVendorsCommand;
use App\Telegram\Inline\RegisterVendor;
use App\Telegram\Inline\TestInline;
use App\Telegram\Middleware\Telegram\AuthVendorIfExistsMiddleware;
use App\Telegram\Middleware\Telegram\CheckNotRegisteredMiddleware;
use App\Telegram\Middleware\Telegram\IsVendorMiddleware;
use App\Telegram\Middleware\Telegram\UpdateVendorLastActionMiddleware;
use App\Telegram\TelegramVendorClient;

class VendorRoutes
{
    /**
     * @param TelegramVendorClient $vendorBot
     * @return void
     */
    public function registerDefaulRoutes(TelegramVendorClient $vendorBot): void
    {
        $vendorBot->middleware(AuthVendorIfExistsMiddleware::class);
        $vendorBot->middleware(UpdateVendorLastActionMiddleware::class);


        $vendorBot->group(function (TelegramVendorClient $vendorBot) {
            $vendorBot->onCommand('start', RegisterVendor::class);
            $vendorBot->onCallbackQueryData('register', RegisterVendor::class);
        })->middleware(CheckNotRegisteredMiddleware::class);

        $vendorBot->group(closure: function (TelegramVendorClient $vendorBot) {
            $vendorBot->registerCommand(CallVendorMenuCommand::class);
        })->middleware(IsVendorMiddleware::class);
    }


    /**
     * Это регистрация станадартных команд,
     * для отображения их так же надо добавить в registerClientCommands роут, который на
     * проде используется для регистрации команд.
     *
     * @param TelegramVendorClient $vendorBot
     * @return void
     */
    public function registerCommands(TelegramVendorClient $vendorBot): void
    {
        $vendorBot->registerCommand(CallVendorMenuCommand::class);
    }

    /**
     * Это тестоыве команды только для разработки и тестирования
     * при полноценном запуске не должны быть ипользованы.
     * При этом, чтобы они отображались
     * они должны быть в registerVendorCommands роуте, так как для прода его нужно использовать * для регистрации команд.
     *
     * @param TelegramVendorClient $vendorBot
     * @return void
     */
    public function registerTestCommands(TelegramVendorClient $vendorBot): void
    {
        $vendorBot->onCommand('test', callable: TestInline::class);
        $vendorBot->registerCommand(CallRemoveVendorCommand::class);
        $vendorBot->registerCommand(RunFindOrdersForVendorsCommand::class);
    }
}
