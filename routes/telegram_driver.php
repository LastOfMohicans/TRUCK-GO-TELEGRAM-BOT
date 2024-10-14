<?php

/** @var TelegramDriverClient $driverBot */

use App\Telegram\Router\Router;
use App\Telegram\TelegramDriverClient;

// Проверяем находится ли разработка в локальном режиме, так как данный файл исключительно для разработки и тестирования.
if (config("app.env") != 'local') {
    return;
}

$router = new Router();
$driver = $router->driver;
$driver->registerDefaulRoutes($driverBot);

// Команда для тестирования, При вызове callable: TestInline::class - мы вызываем у класса метод start, вызывается в телеграмме /test.
$driver->testDriverCommand($driverBot);

// убрать на проде потому что:
// Please do not use the registerMyCommands method in the same file where you register your bot handlers when using the Webhook running mode, because the bot will register your commands on every webhook call, causing a lot of useless requests. Just call the method manually or after a deploy.
$driverBot->registerMyCommands();

