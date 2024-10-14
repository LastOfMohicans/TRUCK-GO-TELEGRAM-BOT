<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Router\Router;


/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/
// Проверяем находится ли разработка в локальном режиме, так как данный файл исключительно для разработки и тестирования.
if (config("app.env") != 'local') {
    return;
}

$router = new Router();
$client = $router->client;
$client->registerDefaulRoutes($bot);

// Команда для тестирования, удаляет клиента и все связанные данные, а так же производит ручной запуск алгоритма поиска заказов для поставщика вызывается в телеграмме /test.
$client->registerTestCommands($bot);

// убрать на проде потому что:
// Please do not use the registerMyCommands method in the same file where you register your bot handlers when using the Webhook running mode, because the bot will register your commands on every webhook call, causing a lot of useless requests. Just call the method manually or after a deploy.
$bot->registerMyCommands();
