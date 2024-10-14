<?php
declare(strict_types=1);

namespace App\Telegram\Middleware\Telegram;

use App\Services\ClientService;
use App\Services\VendorService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;
use App\Telegram\Inline\RegisterVendor;

class IsVendorMiddleware
{
    public function __invoke(Nutgram $bot, $next, ClientService $userService, VendorService $vendorService): void
    {
        $user = Auth::user();
        if (is_null($user)) {
            RegisterVendor::begin($bot);
            return;
        }
        $next($bot);
    }
}
