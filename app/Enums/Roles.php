<?php
declare(strict_types=1);

namespace App\Enums;

/**
 * Определяет роли у поставщика.
 *
 */
enum Roles: string
{
    /**
     * Роль поставщика.
     */
    case Vendor = 'vendor';

    /**
     * Роль менеджера у поставщика.
     */
    case VendorManager = 'vendor_manager';

    /**
     * Роль менеджера у поставщика с админскими правами.
     */
    case VendorAdminManager = 'vendor_admin_manager';
}

