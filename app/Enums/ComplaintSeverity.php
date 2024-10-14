<?php

namespace App\Enums;

/**
 * Определяет серьезность жалоб.
 *
 */
enum ComplaintSeverity: string
{
    /**
     * Низкая серьезность.
     */
    case Low = 'low';
    /**
     * Средняя серьезность.
     */
    case Medium = 'medium';
    /**
     * Высокая серьезность.
     */
    case High = 'high';
    /**
     * Критическая серьезность.
     */
    case Critical = 'critical';
}
