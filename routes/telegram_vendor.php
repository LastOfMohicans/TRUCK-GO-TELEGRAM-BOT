<?php

/** @var TelegramVendorClient $vendorBot */

use App\Telegram\Router\Router;
use App\Telegram\TelegramVendorClient;

// Проверяем находится ли разработка в локальном режиме, так как данный файл исключительно для разработки и тестирования.
if (config("app.env") != 'local') {
    return;
}

$router = new Router();
$vendor = $router->vendor;
$vendor->registerDefaulRoutes($vendorBot);

// Команда для тестирования, удаляет поставщика и все связанные данные, а так же производит ручной запуск алгоритма поиска заказов для поставщика вызывается в телеграмме /test.
$vendor->registerTestCommands($vendorBot);


// убрать на проде потому что:
// Please do not use the registerMyCommands method in the same file where you register your bot handlers when using the Webhook running mode, because the bot will register your commands on every webhook call, causing a lot of useless requests. Just call the method manually or after a deploy.
$vendorBot->registerMyCommands();

