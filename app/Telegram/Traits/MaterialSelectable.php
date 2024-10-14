<?php

declare(strict_types=1);

namespace App\Telegram\Traits;

use App\Enums\QuestionAnswerType;
use App\Models\Catalog;
use App\Models\Material;
use App\Services\CatalogService;
use App\Services\MaterialService;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * Трейт для выбора материала через каталог.
 * Включает в себя все вопросы о материале.
 */
trait MaterialSelectable
{
    /**
     * Вызов переданный callback после окончания выбора материала.
     *
     * @var string
     */
    public string $callbackMethodName;
    /**
     * ID материала.
     *
     * @var string
     */
    public string $materialID = '';
    /**
     * Количество материала.
     *
     * @var int
     */
    public int $quantity = 0;

    /**
     * Название материала.
     *
     * @var string
     */
    public string $materialName = '';
    /**
     * ID вопроса.
     *
     * @var int
     */
    public int $questionID = 0;
    /**
     * Содержит ответы пользователей на вопросы.
     *
     * @var array
     */
    public array $questionIDToAnswer = [];
    /**
     * ID текущего вопроса для пользователя.
     *
     * @var int
     */
    public int $questionIDInUse = 0;
    /**
     * Массив переходов по каталогам.
     *
     * @var array
     */
    public array $catalogNames = [];

    protected CatalogService $catalogService;
    protected MaterialService $materialService;

    /**
     * Инициализируем трейт, обязательно для корректной работы.
     *
     * @param CatalogService $catalogService
     * @param MaterialService $materialService
     * @return void
     */
    public function initializeMaterialSelectableTrait(CatalogService $catalogService, MaterialService $materialService): void
    {
        $this->catalogService = $catalogService;
        $this->materialService = $materialService;
    }


    /**
     * Входной метод, который обрабатывает запрос на выбор материала и устанавливает callback метод.
     * В нем мы получаем все корни каталога, чтобы по ним добраться до выбора материала.
     *
     * @param Nutgram $bot
     * @param string $callbackMethodName
     * @return void
     * @throws InvalidArgumentException
     */
    public function startSelectMaterial(Nutgram $bot, string $callbackMethodName): void
    {
        $this->clearButtons();
        $this->callbackMethodName = $callbackMethodName;
        $this->handleCatalogChoose($bot, "");
    }

    /**
     * Показываем детей каталога или делаем выбор товара.
     * Если у каталога нет детей, то его айди должен быть присвоен какому-либо материалу, который мы выбираем.
     *
     * @param Nutgram $bot
     * @param string $catalogID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCatalogChoose(Nutgram $bot, string $catalogID): void
    {
        $this->clearButtons();

        /** @var Catalog[] $catalogs */
        $catalogs = Collection::make();
        if ($catalogID == "") {
            $catalogs = $this->catalogService->getRootCatalogs();
        } else {
            $catalogs = $this->catalogService->getCatalogChildren((int)$catalogID);
        }

        if (!is_null($catalogs)) {
            $parentCatalogID = null;
            $question = $catalogs[0]->question; // Берем любой каталог и его вопрос, так как мы запрашиваем детей каталога, а дети всегда одного типа.

            if ($catalogID != "") {
                $catalog = $this->catalogService->firstCatalog((int)$catalogID);
                $this->catalogNames[] = $catalog->name;

                $parentCatalogID = 0;
                if (!is_null($catalog) and !is_null($catalog->parent_id)) {
                    $parentCatalog = $this->catalogService->firstCatalog($catalog->parent_id);
                    $parentCatalogID = $parentCatalog->id;
                }
            }

            $this->showCatalogs($parentCatalogID, $catalogs, $question);
            return;
        }

        $material = $this->materialService->firstMaterialByCatalogID((int)$catalogID);
        if (is_null($material)) {
            $this->startSelectMaterial($bot, $this->callbackMethodName);
            return;
        }
        $this->handleMaterialChoose($material);
    }

    /**
     * Вызывает метод в CreateOrder, который возвращает обратно в главное меню.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handleBackToMenu(Nutgram $bot): void
    {
        $this->backToMenu($bot);
    }

    /**
     * Показываем каталоги.
     *
     * @param int|null $parentCatalogID
     * @param Collection $catalogs
     * @return void
     * @throws InvalidArgumentException
     */
    protected function showCatalogs(?int $parentCatalogID, Collection $catalogs, string $question): void
    {
        $this->clearButtons();

        /** @var Catalog $catalog */
        foreach ($catalogs as $catalog) {
            $this->addButtonRow(
                InlineKeyboardButton::make($catalog->name, callback_data: $catalog->id . "@handleCatalogChoose")
            );
        }

        if (!is_null($parentCatalogID)) {
            if ($parentCatalogID == 0) {
                $this->addButtonRow(
                    InlineKeyboardButton::make('Назад', callback_data: "@handleBackInCatalog")
                );
                $this->addButtonRow(
                    InlineKeyboardButton::make('Вернуться в меню', callback_data: "@handleBackToMenu")
                );
            } else {
                $this->addButtonRow(
                    InlineKeyboardButton::make('Назад', callback_data: $parentCatalogID . "@handleBackInCatalog")
                );
            }
        } else {
            $this->addButtonRow(
                InlineKeyboardButton::make($this->backButton, callback_data: "@handleBackToMenu")
            );
        }

        $this->menuText(
            "{$question}\n" . implode(" ", $this->catalogNames)
        );

        $this->showMenu();
    }


    public function handleBackInCatalog(Nutgram $bot, string $catalogID): void
    {
        array_pop($this->catalogNames);
        if (count($this->catalogNames) > 0) {
            array_pop($this->catalogNames); // Делаем второй раз, потому что мы автоматически записываем каталог в шаг, даже пре нажатие назад.
        }
        $this->handleCatalogChoose($bot, $catalogID);
    }


    /**
     * Обрабатываем выбор материала.
     * Сохраняем выбранный материал и запрашиваем у пользователя количество материала.
     *
     * @param Material $material
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleMaterialChoose(Material $material): void
    {
        $this->materialName = $material->name;
        $this->questionIDInUse = 0;
        $this->materialID = (string)$material->id;

        $this->askQuantityOfMaterial();
    }

    /**
     * Запрашиваем количество материала.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function askQuantityOfMaterial(): void
    {
        $this->menuText('Введите количество материала цифрой. Минимальное количество 1 кубометр.');

        $this->orNext('handleQuantityChosen');
        $this->showMenu(reopen: true);
    }

    /**
     * Обрабатываем ответ о количестве материала.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleQuantityChosen(Nutgram $bot): void
    {
        $value = $bot->message()->text;
        $err = $this->validateQuantityAnswer($value);
        if ($err) {
            $bot->sendMessage($err, $this->chatId);
            $this->askQuantityOfMaterial();
            return;
        }

        $this->quantity = intval($value);

        $this->askMaterialQuestions($bot);
    }

    /**
     * Спрашиваем обязательные вопросы привязанные к материалу.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    private function askMaterialQuestions(Nutgram $bot): void
    {
        $materialService = $this->getMaterialService();
        $materialWithRequiredQuestions = $materialService->getMaterialWithRequiredQuestionsByID($this->materialID);
        if (!$materialWithRequiredQuestions) {
            $this->{$this->callbackMethodName}($bot);
            return;
        }

        if (count($materialWithRequiredQuestions['materialQuestions']) == 0) {
            $this->{$this->callbackMethodName}($bot);
            return;
        }

        $this->makeQuestions($materialWithRequiredQuestions['materialQuestions']);

        $this->showMenu(reopen: true);
    }

    /**
     * Обрабатываем переход к следующему вопросу.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleNextQuestion(Nutgram $bot): void
    {
        $this->clearButtons();

        $materialQuestionsService = $this->getMaterialQuestionsService();
        $questions = $materialQuestionsService->getRequiredQuestions($this->materialID);
        if ($this->questionIDInUse >= count($questions) - 1) {
            $this->{$this->callbackMethodName}($bot);
            return;
        }

        $this->questionIDInUse++;

        $this->makeQuestions($questions);

        $this->showMenu(reopen: true);
    }

    /**
     * Обрабатываем ответ пользователя в виде строки.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAnswerString(Nutgram $bot)
    {
        $err = $this->validateSingleStringAnswer($bot->message()->text);
        if ($err) {
            $bot->sendMessage($err);
            $this->askMaterialQuestions($bot);
            return;
        }
        $value = trim($bot->message()->text);
        $this->clearButtons();
        $this->questionIDToAnswer[$this->questionID] = $value;
        $this->handleNextQuestion($bot);
    }

    /**
     * Обрабатываем ответ пользователя в виде целого числа.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAnswerInt(Nutgram $bot)
    {
        $err = $this->validateIntAnswer($bot->message()->text);
        if (!is_null($err)) {
            $bot->sendMessage($err);
            return;
        }

        $value = trim($bot->message()->text);

        $this->clearButtons();
        $this->questionIDToAnswer[$this->questionID] = $value;
        $this->handleNextQuestion($bot);
    }

    /**
     * Отображаем вопросы пользователю.
     * Выводит текущий вопрос и в зависимости от типа ответа (целое число, строка, выбор) задает соответствующий
     * обработчик.
     *
     * @param Collection $questions
     * @return void
     */
    protected function makeQuestions(Collection $questions)
    {
        $questionID = $questions[$this->questionIDInUse]->id;
        $this->questionID = $questionID;
        $questionValue = $questions[$this->questionIDInUse]->question;
        if ($questions[$this->questionIDInUse]->question_answer_type == QuestionAnswerType::UserInt->value) {
            if (array_key_exists($questionID, $this->questionIDToAnswer)) {
                $this->menuText(
                    "Вопрос: $questionValue\nОтветьте просто цифрой, какое количество нужно? Пример ответа: 14\nВы выбрали {$this->questionIDToAnswer[$questionID]}"
                );
            } else {
                $this->menuText(
                    "Выбранный материал: $this->materialName\n$questionValue\nОтветьте просто цифрой, какое количество нужно? Пример ответа: 14"
                );
            }
            $this->orNext('handleAnswerInt');
        } else {
            if ($questions[$this->questionIDInUse]->question_answer_type == QuestionAnswerType::UserString->value) {
                if (array_key_exists($questionID, $this->questionIDToAnswer)) {
                    $this->menuText(
                        "Вопрос: $questionValue\nОтветьте сообщением, какое количество нужно? Пример ответа: 14\nВы выбрали {$this->questionIDToAnswer[$questionID]}"
                    );
                } else {
                    $this->menuText(
                        "Выбранный материал: $this->materialName\n$questionValue\nОтветьте сообщением, какое количество нужно? Пример ответа: 14"
                    );
                }
                $this->orNext('handleAnswerString');
            } else {
                if ($questions[$this->questionIDInUse]->question_answer_type == QuestionAnswerType::Select->value) {
                    $materialQuestionAnswersService = $this->getMaterialQuestionAnswersService();
                    $questionsAnswers = $materialQuestionAnswersService->getMaterialQuestionAnswer($questionID);

                    foreach ($questionsAnswers as $questionAnswer) {
                        // если айди ответа по айди запроса есть в масиве, ставим его выбранным
                        if (array_key_exists($questionID, $this->questionIDToAnswer)) {
                            if ($this->questionIDToAnswer[$questionID] == $questionAnswer->id) {
                                $this->addButtonRow(
                                    InlineKeyboardButton::make(
                                        $questionAnswer->answer . " ✅",
                                        callback_data: $questionAnswer->id . "@handleAnswerUnChoose"
                                    )
                                );
                                continue;
                            }
                        }
                        $this->addButtonRow(
                            InlineKeyboardButton::make(
                                $questionAnswer->answer,
                                callback_data: $questionAnswer->id . "@handleAnswerChoose"
                            )
                        );
                    }

                    $this->menuText("$questionValue\n Выберите ответ");
                }
            }
        }
    }

    /**
     * Обрабатывает выбор ответа из списка.
     *
     * @param Nutgram $bot
     * @param string $answerID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAnswerChoose(Nutgram $bot, string $answerID): void
    {
        $this->questionIDToAnswer[$this->questionID] = $answerID;
        $bot->answerCallbackQuery();
        $this->clearButtons();
        $this->handleNextQuestion($bot);
    }

    /**
     * Обрабатывает отмену выбора ответа.
     *
     * @param Nutgram $bot
     * @param string $answerID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAnswerUnChoose(Nutgram $bot, string $answerID): void
    {
        $this->clearButtons();
        unset($this->questionIDToAnswer[$this->questionID]);

        $bot->answerCallbackQuery();

        $this->showMenu();
    }
}
