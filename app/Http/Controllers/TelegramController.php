<?php
declare(strict_types=1);

namespace App\Http\Controllers;


use App\Telegram\Router\ClientRoutes;
use App\Telegram\Router\DriverRoutes;
use App\Telegram\Router\Router;
use App\Telegram\Router\VendorRoutes;
use App\Telegram\TelegramVendorClient;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Nutgram;

class TelegramController extends Controller
{
    protected Router $router;
    protected ClientRoutes $clientRoutes;
    protected VendorRoutes $vendorRoutes;
    protected DriverRoutes $driverRoutes;

    public function __construct()
    {
        $this->router = new Router();
        $this->clientRoutes = $this->router->client;
        $this->vendorRoutes = $this->router->vendor;
        $this->driverRoutes =  $this->router->driver;
    }
    /**
     * Запускаем бота для клиента.
     *
     * @param Nutgram $bot
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function startClientBot(Nutgram $bot): void
    {
        $this->clientRoutes->registerDefaulRoutes($bot);
        $this->clientRoutes->registerTestCommands($bot);
        $bot->run();
    }

    /**
     * Запускаем бота для поставщика.
     *
     * @param TelegramVendorClient $bot
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function startVendorBot(TelegramVendorClient $vendorBot): void
    {
        $this->vendorRoutes->registerDefaulRoutes($vendorBot);
        $this->vendorRoutes->registerTestCommands($vendorBot);
        $vendorBot->run();
    }

    /**
     * Метод регистрации в телеграм команд для клиента.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function registerClientCommands(Nutgram $bot): void
    {
        $this->clientRoutes->registerCommands($bot);
        $this->clientRoutes->registerTestCommands($bot);
        $bot->registerMyCommands();
    }

    /**
     *  Метод регистрации в телеграм команд для поставщика.
     *
     * @param TelegramVendorClient $bot
     * @return void
     */
    public function registerVendorCommands(TelegramVendorClient $vendorBot)
    {
        $this->vendorRoutes->registerCommands($vendorBot);
        $this->vendorRoutes->registerTestCommands($vendorBot);
        $vendorBot->registerMyCommands();
    }
}
