<?php
declare(strict_types=1);

namespace App\Enums;

/**
 * Определяет тип поставщика.
 *
 */
enum VendorType: string
{
    /**
     * Когда поставщик ИП.
     */
    case Individual = 'individual';

    /**
     * Когда поставщик ООО.
     */
    case Company = 'company';
}
