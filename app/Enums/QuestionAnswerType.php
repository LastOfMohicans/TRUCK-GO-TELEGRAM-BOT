<?php
declare(strict_types=1);

namespace App\Enums;

/**
 * Определяет тип ответа для вопроса.
 */
enum QuestionAnswerType: string
{
    /**
     * Выбор из нескольких вариантов ответа.
     */
    case Select = 'select';

    /**
     * Нужно ввести число.
     */
    case UserInt = 'user_enter_int';

    /**
     * Нужно ввести слово/предложение.
     */
    case UserString = 'user_enter_string';
}
