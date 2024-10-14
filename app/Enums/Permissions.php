<?php

namespace App\Enums;

/**
 * Определяет разрешенные действия у поставщика.
 *
 */
enum Permissions: string
{
    /**
     * Возможность редактирования профиля компания.
     */
    case EditProfile = 'edit_profile';
}
