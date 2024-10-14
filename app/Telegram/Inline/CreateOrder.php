<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Enums\QuestionAnswerType;
use App\Exceptions\FailedToCreateOrderException;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\CatalogService;
use App\Services\DeliveryService;
use App\Services\MaterialService;
use App\Services\OrderService;
use App\Telegram\Traits\ClientMenuSelectable;
use App\Telegram\Traits\Listable;
use App\Telegram\Traits\MaterialSelectable;
use App\Telegram\Traits\OrderDateTimeSelectable;
use Illuminate\Support\Facades\Auth;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class CreateOrder extends OrderBase
{
    use Listable;
    use ClientMenuSelectable;
    use OrderDateTimeSelectable;
    use MaterialSelectable;

    protected Client $client;

    public ?string $comment = null;

    public function __construct(CatalogService $catalogService, MaterialService $materialService, OrderService $orderService, DeliveryService $deliveryService)
    {
        parent::__construct();

        $this->client = Auth::user();
        $this->initializeClientMenuSelectableTrait($orderService, $deliveryService);
        $this->initializeMaterialSelectableTrait($catalogService, $materialService);
    }

    public function start(Nutgram $bot): void
    {
        $this->startSelectMaterial($bot, 'preAskAddress');
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function preAskAddress(Nutgram $bot): void
    {
        $this->askAddress($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function internalHandleAddressAnswer(Nutgram $bot): void
    {
        $this->askOrderDeliveryDateTime($bot, 'internalASkDeliveryDate');
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function internalASkDeliveryDate(Nutgram $bot): void
    {
        $this->askComment($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function askComment(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->menuText('Хотите ли вы добавить комментарий к заказу? Если хотите, отправьте комментариий текстом.');
        $this->addButtonRow(InlineKeyboardButton::make('Продолжить без комментария.', callback_data: '@handleWithoutComment'));
        $this->orNext('saveComment');

        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleWithoutComment(Nutgram $bot): void
    {
        $this->comment = null;

        $this->finishOrder($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function saveComment(Nutgram $bot): void
    {
        $commentOrder = $bot->message()->text;
        if (!$commentOrder) {
            $this->askComment($bot);
            return;
        }

        $this->comment = $commentOrder;
        $this->finishOrder($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function finishOrder(Nutgram $bot): void
    {
        $this->clearButtons();

        $questionsService = $this->getMaterialQuestionsService();
        $questions = $questionsService->getRequiredQuestionsWithAnswers($this->materialID);
        $questionsText = '';
        foreach ($questions as $question) {
            if (!array_key_exists($question->id, $this->questionIDToAnswer)) {
                continue;
            }
            $valueOrID = $this->questionIDToAnswer[$question->id];
            if ($question->question_answer_type == QuestionAnswerType::Select->value) {
                foreach ($question->activeMaterialQuestionAnswers as $questionAnswer) {
                    if ($valueOrID == $questionAnswer->id) {
                        $valueOrID = $questionAnswer->answer;
                    }
                }
            }

            $questionsText .= $question->question . "- $valueOrID\n";
        }

        $orderService = $this->getOrderService();

        $delivery = new Delivery();
        $delivery->latitude = $this->address->getLatitude();
        $delivery->longitude = $this->address->getLongitude();
        $delivery->address = $this->address->getAddress();
        $delivery->wanted_delivery_window_start = $this->wanted_delivery_window_start;
        $delivery->wanted_delivery_window_end = $this->wanted_delivery_window_end;

        $client = Auth::user();

        $order = new Order();
        $order->material_id = $this->materialID;
        $order->is_activated = true;
        $order->is_finished = true;
        $order->client_id = $client->id;
        $order->quantity = $this->quantity;
        $order->comment = $this->comment;

        try {
            $order = $orderService->createOrder($order, $delivery, $this->questionIDToAnswer);
        } catch (FailedToCreateOrderException $e) {
            report($e);
            // TODO:: реализация на не запланированное поведение

            $bot->sendMessage($e->getMessage());
            return;
        }

        $text = "Отлично, заказ создан! Я уже ищу поставщика. Каĸ только поставщик подтвердит заказ, я пришлю вам уведомление!\n";
        $text .= "А пока можно заняться любимым делом.\n";
        $text .= "Детали заказа:\n";
        $text .= $questionsText;
        $text .= "Номер заказа: {$order->id}\n";
        $text .= "Дата доставки: {$delivery->wanted_delivery_window_start}-{$delivery->wanted_delivery_window_end}\n";

        $text .= "Адрес доставĸи:\n";
        $text .= "Широта - {$delivery->latitude}\n"
            . "Долгота - {$delivery->longitude}\n";
        $text .= "Регион - {$this->address->getRegion()}\n";

        if (!is_null($this->address->getPostalCode())) {
            $text .= "Посталкоде - {$this->address->getPostalCode()}\n";
        }

        $text .= "Адрес - {$delivery->address}\n";

        $text .= "Комментарий: {$order->comment}\n";

        $bot->sendMessage(
            text: $text
        );
        $this->end();

        $orderCount = $this->orderService->countActiveOrders($client->id);
        $this->handleShowActiveOrders($bot, (string)$orderCount);
    }

    protected function getAskAddressMessage(): string
    {
        return 'Время я уточню немного позже, а пока отправьте адрес куда нужно привезти заказ. В любом удобном, из следующих форматов:
– Поделитесь вашей геолоĸацией (отправьте мне в ответ вашу геолокацию)
– Точный адрес в формате: садовое товарищество Зелёный Бор-1, д. 6, городской округ Пушкинский, Московская область.
– Отправьте точные координаты в формате: 55.967467, 37.870123';
    }

}
