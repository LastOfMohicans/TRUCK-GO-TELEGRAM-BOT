<?php
declare(strict_types=1);

namespace App\Telegram\Traits;


use Illuminate\Pagination\LengthAwarePaginator;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * Трейт для создания кнопок в листинге дальше и назад.
 * Не дает перейти на несуществующие страницы.
 */
trait Listable
{
    public int $listLastPage = 0;

    public string $methodName = '';
    public ?string $nextPageFunctionName = '';
    public ?string $previousPageFunctionName = '';

    /**
     * Основной метод который нужно вызывать.
     * Добавляет кнопки вперед и назад, не дает перейти на несуществующие страницы.
     *
     * @param LengthAwarePaginator $data
     * @param string $methodName               Название метода который вызывать при нажатии кнопки след/пред страница.
     *                                         Метод должен принимать первым параметром bot, вторым параметром page.
     * @param string $nextPageFunctionName     Метод для вызова при обработке кнопки "следующая страница". Работает
     *                                         только если $methodName пустой.
     * @param string $previousPageFunctionName Метод для вызова при обработке кнопки "предыдущая страница". Работает
     *                                         только если $methodName пустой.
     * @return void
     */
    protected function makeList(LengthAwarePaginator $data, string $methodName, string $nextPageFunctionName = "", string $previousPageFunctionName = ""): void
    {
        $this->listLastPage = $data->lastPage();

        $this->methodName = $methodName;
        $this->nextPageFunctionName = $nextPageFunctionName;
        $this->previousPageFunctionName = $previousPageFunctionName;

        $pages = [];

        if (!$data->onFirstPage()) {
            $previousPage = $data->currentPage() - 1;
            $pages[] = InlineKeyboardButton::make("Предыдущая страница", callback_data: $previousPage . "@handlePreviousPage");
        }

        if ($data->currentPage() != $data->lastPage()) {
            $nextPage = $data->currentPage() + 1;
            $pages[] = InlineKeyboardButton::make("Следующая страница", callback_data: $nextPage . "@handleNextPage");
        }

        if (count($pages) > 0) {
            $this->addButtonRow(...$pages);
        }
    }


    /**
     * Обработка следующей страницы.
     *
     * @param Nutgram $bot
     * @param string $page
     * @return void
     */
    protected function handleNextPage(Nutgram $bot, string $page): void
    {
        if ($page >= $this->listLastPage) {
            if ($this->callCustomMethod($bot, $page)) {
                return;
            }

            $this->{$this->nextPageFunctionName}($bot, $this->listLastPage);
            return;
        }

        if ($this->callCustomMethod($bot, $page)) {
            return;
        }

        $this->{$this->nextPageFunctionName}($bot, $page);
    }

    /**
     * Обработка предыдущей страницы.
     *
     * @param Nutgram $bot
     * @param string $page
     * @return void
     */
    protected function handlePreviousPage(Nutgram $bot, string $page): void
    {
        if ($this->validatePageIsLessThanOne($page)) {
            if ($this->callCustomMethod($bot, $page)) {
                return;
            }

            $this->{$this->previousPageFunctionName}($bot, 1);
            return;
        }

        if ($this->callCustomMethod($bot, $page)) {
            return;
        }

        $this->{$this->previousPageFunctionName}($bot, $page);
    }


    protected function callCustomMethod(Nutgram $bot, string $page): bool
    {
        if ($this->methodName == "") {
            return false;
        }

        $this->{$this->methodName}($bot, $page);

        return true;
    }

    protected function validatePageIsLessThanOne($page): bool
    {
        return $page <= 0;
    }
}
