<?php

namespace App\Enums;

/**
 * Определяет идентификатор жалобы.
 *
 */
enum ComplaintReason: int
{
    /**
     * Такой ИНН существует и я хочу сменить ответственного.
     */
    case INNExistsAndWannaChange = 1;
    /**
     * Такой ИНН существует, но никогда не работали с нами.
     */
    case INNExistsAndDidNotWorkWithUS = 2;
    /**
     * Такой ИНН существует.
     */
    case INNExistsAndOtherRequest = 3;
}
