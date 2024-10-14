<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Exceptions\FailedToCreateClientException;
use App\Models\Client;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class RegisterClient extends InlineMenuBase
{
    public function start(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $bot->answerCallbackQuery();
        }

        $this->menuText('Прошу ознакомиться с договором и политикой в области обработки, хранения персональных данных.');

        $this->addButtonRow(InlineKeyboardButton::make('Ознакомиться', url: 'https://www.google.ru/'));
        $this->addButtonRow(InlineKeyboardButton::make('Я ознакомился', callback_data: "@handleAcknowledgement"));

        $this->showMenu();
    }

    public function handleAcknowledgement(Nutgram $bot): void
    {
        $this->clearButtons();

        try {
            $client = new Client();
            $client->telegram_chat_id = $bot->chatId();
            $this->getClientService()->createClient($client);
        } catch (FailedToCreateClientException $e) {
            // TODO:: реализация на не запланированное поведение
            $bot->sendMessage($e->getMessage());
        }

        $this->end();

        CreateOrder::begin($bot);
    }
}
