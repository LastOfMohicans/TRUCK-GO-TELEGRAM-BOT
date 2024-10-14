<?php
declare(strict_types=1);

namespace App\Providers;

use App\Telegram\TelegramVendorClient;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nutgram\Laravel\Mixins\FileMixin;
use Nutgram\Laravel\Mixins\NutgramMixin;
use Nutgram\Laravel\RunningMode\LaravelWebhook;
use Psr\Log\LoggerInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\RunningMode\Polling;
use SergiX44\Nutgram\Telegram\Types\Media\File;
use SergiX44\Nutgram\Testing\FakeNutgram;

class TelegramVendorServiceProvider extends ServiceProvider
{
    public string $telegramRoutes;

    public function register()
    {
        $this->telegramRoutes = $this->app->basePath('/routes/telegram_vendor.php');

        $this->app->singleton(TelegramVendorClient::class, function (Application $app) {
            $configuration = new Configuration(
                apiUrl: config('nutgram.config.api_url', Configuration::DEFAULT_API_URL),
                botId: config('nutgram.config.bot_id'),
                botName: config('nutgram.config.bot_name'),
                testEnv: config('nutgram.config.test_env', false),
                isLocal: config('nutgram.config.is_local', false),
                clientTimeout: config('nutgram.config.timeout', Configuration::DEFAULT_CLIENT_TIMEOUT),
                clientOptions: config('nutgram.config.client', []),
                container: $app,
                hydrator: config('nutgram.config.hydrator', Configuration::DEFAULT_HYDRATOR),
                cache: $app->get(Cache::class),
                logger: $app->get(LoggerInterface::class)->channel(config('nutgram.log_channel', 'null')),
                localPathTransformer: config('nutgram.config.local_path_transformer'),
                pollingTimeout: config('nutgram.config.polling.timeout', Configuration::DEFAULT_POLLING_TIMEOUT),
                pollingAllowedUpdates: config('nutgram.config.polling.allowed_updates',
                    Configuration::DEFAULT_ALLOWED_UPDATES),
                pollingLimit: config('nutgram.config.polling.limit', Configuration::DEFAULT_POLLING_LIMIT),
                enableHttp2: config('nutgram.config.enable_http2', Configuration::DEFAULT_ENABLE_HTTP2),
            );

            $bot = new TelegramVendorClient(config('nutgram.vendor_token') ?? FakeNutgram::TOKEN, $configuration);

            if ($app->runningInConsole()) {
                $bot->setRunningMode(Polling::class);
            } else {
                $webhook = LaravelWebhook::class;

                if (config('nutgram.safe_mode', false)) {
                    $webhook = new LaravelWebhook(
                        getToken: fn() => request()?->header('X-Telegram-Bot-Api-Secret-Token'),
                        secretToken: md5(config('app.key'))
                    );
                    $webhook->setSafeMode(true);
                }

                $bot->setRunningMode($webhook);
            }

            return $bot;
        });

        $this->app->alias(TelegramVendorClient::class, 'telegram_vendor');

        $this->app->singleton('telegram_vendor', fn(Application $app) => $app->get(TelegramVendorClient::class));

        if (config('nutgram.mixins', false)) {
            TelegramVendorClient::mixin(new NutgramMixin());
            File::mixin(new FileMixin());
        }
    }

    public function boot(): void
    {
        if (config('nutgram.routes', false)) {
            $vendorBot = $this->app->get(TelegramVendorClient::class);
            require $this->telegramRoutes;
        }

    }
}
