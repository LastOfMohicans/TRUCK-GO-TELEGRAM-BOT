<?php

namespace App\Telegram\Middleware\Telegram;

use App\Services\ClientService;
use App\Services\VendorService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

/**
 * Middleware отвечающий за активность поставщика в телеграмме.
 * Если поставщик залогинен и делает какое-либо действие, то мы проставляем ему активность в текущий момент времени.
 */
class UpdateVendorLastActionMiddleware
{

    public function __invoke(Nutgram $bot, $next, VendorService $vendorService)
    {
        if (Auth::check()) {
            $vendorService->updateLastTelegramAction(Auth::id());
            return $next($bot);
        }
        return $next($bot);
    }
}
