<?php
declare(strict_types=1);

namespace App\Telegram\Middleware\Telegram;

use App\Services\VendorService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

/**
 *
 * Глобальный мидлвеер, для установки в facade залогиненого поставщика.
 * Мы используем его глобально, а не для групп. Так как inline_menu при переходе к след шагу, не вызывают групповой
 * мидлваре.
 *
 **/
class AuthVendorIfExistsMiddleware
{
    public function __invoke(Nutgram $bot, $next, VendorService $vendorService): void
    {
        if (is_null($bot->chatId())) {
            $next($bot);
            return;
        }

        $vendor = $vendorService->getVendorByTelegramChatID((string)$bot->chatId());
        if (is_null($vendor)) {
            $next($bot);
            return;
        }

        Auth::login($vendor);

        $next($bot);
    }
}
