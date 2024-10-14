<?php
declare(strict_types=1);

namespace App\Telegram\Middleware\Telegram;

use App\Services\ClientService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;
use App\Telegram\Inline\RegisterClient;

class IsClientMiddleware
{
    public function __invoke(Nutgram $bot, $next, ClientService $userService): void
    {
        $user = Auth::user();
        if (is_null($user)) {
            RegisterClient::begin($bot);
            return;
        }
        $next($bot);
    }
}
