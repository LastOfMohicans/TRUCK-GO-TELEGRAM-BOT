<?php

namespace App\Telegram\Middleware\Telegram;

use App\Services\ClientService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

/**
 * Middleware отвечающий за активность клиента в телеграмме.
 * Если клиент залогинен и делает какое-либо действие, то мы проставляем ему активность в текущий момент времени.
 */
class UpdateClientLastActionMiddleware
{

    public function __invoke(Nutgram $bot, $next, ClientService $clientService)
    {
        if (Auth::check()) {
            $clientService->updateLastTelegramAction(Auth::id());
            return $next($bot);
        }
        return $next($bot);
    }
}
