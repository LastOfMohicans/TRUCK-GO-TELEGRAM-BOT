<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TestInline extends InlineMenuBase
{

    // публичное свойство которое кешируется
    // используется для сохранения данных, если они нужны на следующих стадиях меню
    public $test = 123;

    /**
     * @param Nutgram $bot
     * @param $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function start(Nutgram $bot, $page = 1)
    {
        $this->menuText("i'm my command"); // задаем текст для меню


        $this->test = "new data"; // меняем значение в публичном свойстве

        // callback_data: "собака@secondStep" до @ идет аргумент в следующий метод, после - название метода
        // Для обработки кнопки, мы всегда создаем метод с названием handleMETHOD_ACTION.
        $this->addButtonRow(InlineKeyboardButton::make("To second step", callback_data: "собака@handleSecondStep"));
        $this->addButtonRow(InlineKeyboardButton::make("To second step", callback_data: "собака@handleSecondStep"));
        $this->addButtonRow(InlineKeyboardButton::make("To second step", callback_data: "собака@handleSecondStep"));

        $this->showMenu(); // включаем меню
    }


    /**
     * @param Nutgram $bot
     * @param string $arg
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleSecondStep(Nutgram $bot, string $arg)
    {
        $this->clearButtons(); // Удаляем все кнопки которые были добавлены до этого момента

        // в обработчике кнопки, мы только принимаем данные, валидируем их и тд
        // всю остальную логику мы выносим в отдельный protected метод, который вызываем в обработчике.
        // название начинается с глагола, например showActiveOrders и тд
        $this->doSomeLogic($bot, $arg);
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    protected function doSomeLogic(Nutgram $bot, string $arg)
    {
        // отправляем сообщение отдельной сущностью, то есть мы не должны для этого вызывать shopMenu и тд,
        // это просто будет как отдельно сообщение в телеграмме
        $bot->sendMessage($arg);

        $bot->sendMessage($this->test); // должны получить значение не 123, а то которое установили в start то есть new data

        $this->menuText("new text"); // обновляем сообщение в самом меню
        $this->showMenu();  // обновляем меню, без этого новый текст не отобразиться и кнопки не пропадут

        // Так мы можем запустить в исполнение другое inline меню, при этом нынешнее закроется и исчезнет у юзера.
        RegisterClient::begin($bot);
    }

}
