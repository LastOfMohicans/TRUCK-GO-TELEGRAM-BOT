<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Enums\QuestionAnswerType;
use App\Exceptions\FailedToChangeOrderQuestionAnswerException;
use App\Models\OrderQuestionAnswer;
use App\Services\MaterialQuestionAnswersService;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class EditOrder extends OrderBase
{
    public string $orderID = '';

    public string $materialID = '';

    public int $questionID = 0;

    public string $questionValue = '';

    public string $questionAnswerType = '';

    public string $clientID;

    public function start(Nutgram $bot, string $orderID, string $clientID)
    {
        $this->clientID = $clientID;
        $order = $this->getOrder(orderID: $orderID, clientID: $clientID);
        if (is_null($order)) {
            $bot->sendMessage(
                "Невозможно изменять заказ $orderID"
            );
            $this->end();
            return;
        }

        $this->orderID = $orderID;

        $this->addButtonRow(InlineKeyboardButton::make("Изменить ответы на вопросы", callback_data: $order->material_id . "@handleQuestionChoose"));
        if ($order->is_activated) {
            $this->addButtonRow(InlineKeyboardButton::make("Деактивировать заказ", callback_data: $orderID . "@handleDeactivateOrder"));
        } else {
            $this->addButtonRow(InlineKeyboardButton::make("Активировать заказ", callback_data: $orderID . "@handleActivateOrder"));
        }

        $this->addButtonRow(InlineKeyboardButton::make("Отменить заказ", callback_data: $orderID . "@handleCancelOrder"));


        $this->menuText('Что вы хотите сделать с заказом?');
        $this->showMenu(reopen: true);
    }

    public function handleQuestionChoose(Nutgram $bot, string $materialID)
    {
        $this->clearButtons();
        $questions = $this->getMaterialQuestionsService()->getActiveQuestions($materialID);

        $this->questionID = 0;
        $this->materialID = $materialID;
        foreach ($questions as $question) {
            $this->addButtonRow(InlineKeyboardButton::make($question->question, callback_data: $question->id . "@handleChosenQuestion"));
        }

        if ($bot->isCallbackQuery()) {
            $bot->answerCallbackQuery();
        }
        $this->addButtonRow(InlineKeyboardButton::make("назад", callback_data: "$materialID@empty"));

        $this->menuText("Выберите вопрос");
        $this->showMenu();
    }

    public function empty()
    {

    }

    public function handleChosenQuestion(Nutgram $bot, int $questionID)
    {
        $this->clearButtons();

        if ($bot->isCallbackQuery()) {
            $bot->answerCallbackQuery();
        }


        if (empty($this->questionValue) || empty($this->questionID) || empty($this->questionAnswerType)) {
            $questions = $this->getMaterialQuestionsService()->getActiveQuestionsByQuestionsIDs([$questionID]);
            if (count($questions) <= 0) {
                return;
            }
            $question = $questions[0];
            $this->questionID = $questionID;
            $this->questionAnswerType = $question->question_answer_type;
            $this->questionValue = $question->question;
        }

        $this->makeAnswersButtons($this->questionID, $this->questionAnswerType, $this->questionValue);

        $this->addButtonRow(InlineKeyboardButton::make("назад", callback_data: $this->materialID . "@handleQuestionChoose"));


        $this->showMenu();
    }


    public function handleActivateOrder(Nutgram $bot, string $orderID)
    {
        $bot->answerCallbackQuery();
    }

    public function handleDeactivateOrder(Nutgram $bot, string $orderID)
    {
        $bot->answerCallbackQuery();
    }

    function handleAnswerChoose(Nutgram $bot, string $answerID): void
    {
        $this->clearButtons();
        $bot->answerCallbackQuery();

        try {
            $this->getOrderService()->changeOrderQuestionAnswer(
                $this->orderID,
                $this->questionID,
                $this->questionAnswerType,
                $answerID,
            );
        } catch (FailedToChangeOrderQuestionAnswerException $e) {
            // TODO:: реализация на не запланированное поведение
        }

        $this->handleChosenQuestion($bot, $this->questionID);
    }

    public function handleAnswerString(Nutgram $bot)
    {
        $err = $this->validateSingleStringAnswer($bot->message()->text);
        if ($err) {
            $bot->sendMessage($err);
            return;
        }

        $value = trim($bot->message()->text);

        try {
            $this->getOrderService()->changeOrderQuestionAnswer(
                $this->orderID,
                $this->questionID,
                $this->questionAnswerType,
                $value,
            );
        } catch (FailedToChangeOrderQuestionAnswerException $e) {
            // TODO:: реализация на не запланированное поведение
        }

        $this->closeMenu();
        $this->handleChosenQuestion($bot, $this->questionID);
    }

    public function handleAnswerInt(Nutgram $bot)
    {
        $err = $this->validateIntAnswer($bot->message()->text);
        if (!is_null($err)) {
            $bot->sendMessage($err);
            return;
        }

        $value = trim($bot->message()->text);

        try {
            $this->getOrderService()->changeOrderQuestionAnswer(
                $this->orderID,
                $this->questionID,
                $this->questionAnswerType,
                $value,
            );
        } catch (FailedToChangeOrderQuestionAnswerException $e) {
            // TODO:: реализация на не запланированное поведение
        }

        $this->closeMenu();
        $this->handleChosenQuestion($bot, $this->questionID);
    }

    protected function makeAnswersButtons(int $questionID, string $questionAnswerType, string $questionValue): void
    {
        $questionIDToAnswer = $this->makeQuestionIDToAnswer($questionID);

        if ($questionAnswerType == QuestionAnswerType::Select->value) {
            $this->makeAnswersButtonsSelected($questionID, $questionIDToAnswer);
            $this->menuText("$questionValue\n Выберите ответ");
        } else if ($questionAnswerType == QuestionAnswerType::UserInt->value) {
            if (array_key_exists($questionID, $questionIDToAnswer)) {
                $this->menuText("Вопрос: $questionValue\nОтветьте просто цифрой, какое количество нужно? Пример ответа: 14\nВы выбрали {$questionIDToAnswer[$questionID]->answer}");
            } else {
                $this->menuText("Вопрос: $questionValue\nОтветьте просто цифрой, какое количество нужно? Пример ответа: 14");
            }

            $this->orNext('handleAnswerInt');
        } else if ($questionAnswerType == QuestionAnswerType::UserString->value) {
            if (array_key_exists($questionID, $questionIDToAnswer)) {
                $this->menuText("Вопрос: $questionValue\nОтветьте просто сообщением, какое количество нужно? Пример ответа: два\nВы выбрали {$questionIDToAnswer[$questionID]->answer}");
            } else {
                $this->menuText("Вопрос: $questionValue\nОтветьте просто сообщением, какое количество нужно? Пример ответа: два");
            }

            $this->orNext('handleAnswerString');
        }
    }

    /**
     * @param string $questionID
     * @param array $questionIDToAnswer
     * @return void
     */
    protected function makeAnswersButtonsSelected(string $questionID, array $questionIDToAnswer): void
    {
        $questionsAnswers = App::get(MaterialQuestionAnswersService::class)->getMaterialQuestionAnswer($questionID);

        foreach ($questionsAnswers as $questionAnswer) {
            // если айди ответа по айди запроса есть в масиве, ставим его выбранным
            if (array_key_exists($questionID, $questionIDToAnswer)) {
                /** @var OrderQuestionAnswer $orderQuestionAnswer */
                $orderQuestionAnswer = $questionIDToAnswer[$questionID];
                if ($orderQuestionAnswer->material_question_answer_id == $questionAnswer->id) {
                    $this->addButtonRow(InlineKeyboardButton::make($questionAnswer->answer . " ✅", callback_data: $questionAnswer->id . "@empty"));
                    continue;
                }
            }
            $this->addButtonRow(InlineKeyboardButton::make($questionAnswer->answer, callback_data: $questionAnswer->id . "@handleAnswerChoose"));
        }
    }

    protected function makeQuestionIDToAnswer(int $questionID): array
    {
        $orderAnswers = $this->getOrderService()->getAnswers($this->orderID, $questionID);
        $questionIDToAnswer = [];

        foreach ($orderAnswers as $orderAnswer) {
            /** @var OrderQuestionAnswer $orderAnswer */

            $questionIDToAnswer[$orderAnswer->material_question_id] = $orderAnswer;
        }

        return $questionIDToAnswer;
    }
}
