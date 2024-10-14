<?php
declare(strict_types=1);

namespace App\Telegram\Traits;


use Carbon\Exceptions\InvalidFormatException;
use DateTime;
use Illuminate\Support\Carbon;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * Трейт для определения даты заказа при создание заказа.
 * Уточняет дату и время по интервалу.
 * Использовать нужно только входной метод.
 */
trait OrderDateTimeSelectable
{
    /**
     * Начальная дата/время, которая заполняется после успешного завершения определения даты времени заказа.
     *
     * @var string
     */
    public string $wanted_delivery_window_start;
    /**
     * Крайняя дата/время, которая заполняется после успешного завершения определения даты времени заказа.
     *
     * @var string
     */
    public string $wanted_delivery_window_end;

    /**
     * Определятся какой метод вызвать после успешного завершения запроса даты времени.
     *
     * @var string
     */
    public string $callbackMethodName;

    const string DateFormat = 'd-m-Y';

    public string $date = '';

    const string FIRST_INTERVAL = "8:00-12:00";
    const string SECOND_INTERVAL = "12:00-16:00";
    const string THIRD_INTERVAL = "16:00-20:00";

    const array INTERVALS = [
        self::FIRST_INTERVAL => self::FIRST_INTERVAL,
        self::SECOND_INTERVAL => self::SECOND_INTERVAL,
        self::THIRD_INTERVAL => self::THIRD_INTERVAL,
    ];
    const array INTERVAL_TO_TIME_STRING = [
        self::FIRST_INTERVAL => ["8:00:00", "12:00:00"],
        self::SECOND_INTERVAL => ["12:00:00", "16:00:00"],
        self::THIRD_INTERVAL => ["16:00:00", "20:00:00"],
    ];

    /**
     * Входной метод для вызова цепочки запроса даты/времени для заказа.
     * После выполнения вызовется метод переданный в $callbackMethodName.
     * Заполненные данные будут в переменных $wanted_delivery_window_start и $wanted_delivery_window_end
     *
     * @param string $callbackMethodName
     * @return void
     * @throws InvalidArgumentException
     */
    public function askOrderDeliveryDateTime(Nutgram $bot,string $callbackMethodName): void
    {
        $this->clearButtons();

        $this->callbackMethodName = $callbackMethodName;

        $now = Carbon::now();
        // TODO определится с таимзононой
        //$now->setTimezone('UTC');
        //$this->address->timezone;
        if ($now->hourOfDay() >= 19) {
            $this->menuText("Сегодня доставить заказ уже не получится, привезти заказ завтра или запланировать другой день?");
            $this->addButtonRow(InlineKeyboardButton::make("Привезти завтра.", callback_data: "@handleSetDeliveryDateTomorrow"));
        } else {
            $this->menuText("Заказ нужно привезти сегодня или запланировать другую дату?");
            $this->addButtonRow(InlineKeyboardButton::make("Да, сегодня.", callback_data: "@handleDeliveryDateToday"));
        }
        $this->addButtonRow(InlineKeyboardButton::make("Запланировать на другой день.", callback_data: "@handleSetDeliveryDateOtherDay"));

        $this->orNext("askWhenDeliveryOrder");
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleDeliveryDateToday(Nutgram $bot): void
    {
        $this->date = Carbon::now()->format(self::DateFormat);
        $this->askTime($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     */
    public function handleSetDeliveryDateTomorrow(Nutgram $bot): void
    {
        $this->date = Carbon::now()->addDay()->format(self::DateFormat);
        $this->askTime($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleSetDeliveryDateOtherDay(Nutgram $bot): void
    {
        $this->askDate($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function askDate(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText("Укажите дату в формате - 02.03.2024 (день.месяц.год)");

        $this->orNext('nextGetDate');
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function nextGetDate(Nutgram $bot): void
    {
        $dateString = trim($bot->message()->text);

        if (mb_substr($dateString, -1) == '.') {
            $dateString = mb_substr($dateString, 0, -1);
        }

        if (mb_substr($dateString, -1) == 'г' || mb_substr($dateString, -1) == 'Г') {
            $dateString = mb_substr($dateString, 0, -1);
        }

        try {
            $date = Carbon::parse($dateString);
        } catch (InvalidFormatException $e) {
            $this->askDate($bot);
            return;
        }
        $errors = DateTime::getLastErrors();
        if ($errors) {
            $bot->sendMessage('Неправильный формат даты');
            $this->askDate($bot);
            return;
        }

        $now = Carbon::now();
        if ($now->gt($date)) {
            $bot->sendMessage('Дата заказа должна быть не раньше чем сегодня.');
            $this->askDate($bot);
            return;
        }

        $this->date = $date->format(self::DateFormat);
        $this->askTime($bot);
    }


    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function askTime(Nutgram $bot): void
    {
        $this->clearButtons();
        $date = Carbon::parse($this->date);

        if ($date->isToday()) {
            $this->whenDeliveryToday($bot);
            return;
        }

        foreach (self::INTERVALS as $interval) {
            $this->addButtonRow(InlineKeyboardButton::make($interval, callback_data: "{$interval}@handleChooseInterval"));
        }

        $this->menuText('Выберите интервал доставки');
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $interval
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleChooseInterval(Nutgram $bot, string $interval): void
    {
        if (!isset(self::INTERVALS[$interval])) {
            $this->askTime($bot);
            return;
        }

        $date = Carbon::parse($this->date);

        $this->wanted_delivery_window_start = $date
            ->setTimeFromTimeString(self::INTERVAL_TO_TIME_STRING[$interval][0])
            ->toDateTimeString();

        $this->wanted_delivery_window_end = $date
            ->setTimeFromTimeString(self::INTERVAL_TO_TIME_STRING[$interval][1])
            ->toDateTimeString();


        $this->{$this->callbackMethodName}($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    private function whenDeliveryToday(Nutgram $bot): void
    {
        $now = Carbon::now();
        $hourOfDay = $now->hourOfDay();

        switch ($hourOfDay) {
            case $hourOfDay <= 10:
                $this->addButtonRow(
                    InlineKeyboardButton::make(
                        self::FIRST_INTERVAL, callback_data: self::FIRST_INTERVAL . "@handleChooseInterval"
                    )
                );
            case  $hourOfDay <= 14:
                $this->addButtonRow(
                    InlineKeyboardButton::make(
                        self::SECOND_INTERVAL, callback_data: self::SECOND_INTERVAL . "@handleChooseInterval"
                    )
                );
            case $hourOfDay <= 18:
                $this->addButtonRow(
                    InlineKeyboardButton::make(
                        self::THIRD_INTERVAL, callback_data: self::THIRD_INTERVAL . "@handleChooseInterval"
                    )
                );
        }

        $this->menuText('Выберите интервал доставки');
        $this->showMenu(reopen: true);
    }
}
