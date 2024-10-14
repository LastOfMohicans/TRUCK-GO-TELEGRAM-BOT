<?php
declare(strict_types=1);

namespace App\Telegram\Middleware\Telegram;

use App\Services\ClientService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

/**
 *
 * Глобальный мидлвеер, для установки в facade залогиненого клиента.
 * Мы используем его глобально, а не для групп. Так как inline_menu при переходе к след шагу, не вызывают групповой
 * мидлваре.
 *
 **/
class AuthClientIfExistsMiddleware
{
    public function __invoke(Nutgram $bot, $next, ClientService $userService): void
    {
        if (is_null($bot->chatId())) {
            $next($bot);
            return;
        }

        $client = $userService->getClientByChatID((string)$bot->chatId());
        if (is_null($client)) {
            $next($bot);
            return;
        }

        Auth::login($client);

        $next($bot);
    }
}
