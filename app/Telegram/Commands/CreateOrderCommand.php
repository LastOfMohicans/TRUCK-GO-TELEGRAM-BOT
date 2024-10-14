<?php
declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Inline\CreateOrder;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class CreateOrderCommand extends Command
{
    protected string $command = 'create_order';

    protected ?string $description = 'Создать заказ';

    public function handle(Nutgram $bot): void
    {
        CreateOrder::begin($bot);
    }
}
