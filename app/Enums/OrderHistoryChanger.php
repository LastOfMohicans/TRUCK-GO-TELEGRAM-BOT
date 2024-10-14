<?php
declare(strict_types=1);

namespace App\Enums;

/**
 * Определяет кто изменил статус заказа.
 */
enum OrderHistoryChanger: string
{
    /**
     * Когда статус поменялся приложением.
     */
    case System = 'system';

    /**
     * Когда статус поменялся тех поддержкой.
     */
    case Support = 'support';

    /**
     * Когда статус поменялся поставщиком.
     */
    case Vendor = 'vendor';

    /**
     * Когда статус поменялся клиентом.
     */
    case Client = 'client';
}
