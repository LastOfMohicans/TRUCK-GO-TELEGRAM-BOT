<?php

declare(strict_types=1);

namespace App\Telegram\Router;

use App\Telegram\Commands\CallClientMenuCommand;
use App\Telegram\Commands\CreateOrderCommand;
use App\Telegram\Commands\Test\CallRemoveClientCommand;
use App\Telegram\Commands\Test\RunFindOrdersForVendorsCommand;
use App\Telegram\Controllers\OrderRequestController;
use App\Telegram\Inline\RegisterClient;
use App\Telegram\Inline\TestInline;
use App\Telegram\Middleware\Telegram\AuthClientIfExistsMiddleware;
use App\Telegram\Middleware\Telegram\CheckNotRegisteredMiddleware;
use App\Telegram\Middleware\Telegram\IsClientMiddleware;
use App\Telegram\Middleware\Telegram\UpdateClientLastActionMiddleware;
use App\Telegram\Middleware\Telegram\UpdateVendorLastActionMiddleware;
use SergiX44\Nutgram\Nutgram;

class ClientRoutes
{
    /**
     * @param Nutgram $bot
     * @return void
     */
    public function registerDefaulRoutes(Nutgram $bot): void
    {
        $bot->middleware(AuthClientIfExistsMiddleware::class);
        $bot->middleware(UpdateClientLastActionMiddleware::class);

        $bot->group(function (Nutgram $bot) {
            $bot->onCommand('start', RegisterClient::class);
        })->middleware(CheckNotRegisteredMiddleware::class);
        // for Registered clients only
        $bot->group(closure: function (Nutgram $bot) {
            $this->registerCommands($bot);
        })->middleware(IsClientMiddleware::class);

        $bot->onLocation(function (Nutgram $bot) {
            if (is_null($bot->message()->location)) {
                $bot->sendMessage("Передалась пустота");
                return;
            }

            $location = $bot->message()->location;


            $msg = "Передалась локация" . PHP_EOL;
            $msg .= $location->longitude . PHP_EOL;
            $msg .= $location->latitude . PHP_EOL;
            $msg .= "Радиус погрешности определения локации, от 0 до 1500: " . $location->horizontal_accuracy ?: "НИЧЕГО не переданно" . PHP_EOL;
            $msg .= "Время относительно даты отправки сообщения, в течение которого местоположение может быть обновлено; в секундах: " . $location->live_period ?: "Ничего не переданно" . PHP_EOL;
            $msg .= "Направление движения пользователя в градусах; 1-360: " . $location->heading ?: "Ничего не переданно" . PHP_EOL;
            $msg .= "Максимальное расстояние для оповещений о приближении к другому участнику чата в метрах: " . $location->proximity_alert_radius ?: "Ничего не переданно" . PHP_EOL;

            $bot->sendMessage($msg);
        });
    }

    /**
     * Это регистрация станадартных команд,
     * для отображения их так же надо добавить в registerClientCommands роут, который на
     * проде используется для регистрации команд.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function registerCommands(Nutgram $bot): void
    {
        $bot->registerCommand(CallClientMenuCommand::class);
        $bot->registerCommand(CreateOrderCommand::class);
    }

    /**
     * Это тестоыве команды только для разработки и тестирования
     * при полноценном запуске не должны быть ипользованы.
     * При этом, чтобы они отображались
     * они должны быть в registerClientCommands роуте, так как для прода его нужно использовать * для регистрации команд.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function registerTestCommands(Nutgram $bot): void
    {
        $bot->onCommand('test', callable: TestInline::class);
        $bot->registerCommand(CallRemoveClientCommand::class);
        $bot->registerCommand(RunFindOrdersForVendorsCommand::class);
    }
}
