<?php
declare(strict_types=1);

namespace App\Telegram\Commands\Test;

use App\Services\VendorStorageService;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class RunFindOrdersForVendorsCommand extends Command
{
    protected string $command = 'tr';

    protected ?string $description = 'Ручной запуск алгоритма поиска заказов для поставщика';

    public function handle(Nutgram $bot, VendorStorageService $vendorStorageService): void
    {
        $bot->sendMessage('Пробую запустить алгоритм');
        $vendorStorageService->RunAlgorithmToFindVendorsForOrders();
        $bot->sendMessage('Успешно запустил алгоритм');
    }
}
