<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Clients\Address;
use App\Exceptions\AddressNotFoundException;
use App\Exceptions\EmptyAddressException;
use App\Exceptions\FailedToCreateComplaintException;
use App\Models\Complaint;
use App\Services\ClientService;
use App\Services\ComplaintService;
use App\Services\CoordinateService;
use App\Services\DeliveryService;
use App\Services\ExcelService;
use App\Services\MaterialQuestionAnswersService;
use App\Services\MaterialQuestionsService;
use App\Services\MaterialService;
use App\Services\OrderRequestService;
use App\Services\OrderService;
use App\Services\VendorService;
use App\Services\VendorStorageService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Media\Document;

use function Laravel\Prompts\table;

class InlineMenuBase extends InlineMenu
{
    public ?Address $address = null;
    protected string $backButton = 'Назад';

    /**
     * Запускает цепочку запроса местоположения по адресу, координатам или геолокации
     * в конце вызывает метод internalHandleAddressAnswer.
     * При этом заполняет свойство $address.
     *
     * @param Nutgram $bot
     * @param string $text
     * @return void
     * @throws InvalidArgumentException
     */
    public function askAddress(Nutgram $bot, string $text = 'Или вернитесь назад'): void
    {
        $this->clearButtons();
        $bot->sendMessage(
            text: $this->getAskAddressMessage(),
            reply_markup: ReplyKeyboardMarkup::make(resize_keyboard: true, one_time_keyboard: true)
                ->addRow(
                    KeyboardButton::make('Поделиться геолокацией', request_location: true),
                ),
        );

        $this->menuText($text);
        $this->internalAskAddress($bot);
        $this->orNext('handleAddressAnswer');

        $this->showMenu(reopen: true);
    }

    protected function getAskAddressMessage(): string
    {
        return 'переопредилить и заполнить';
    }

    /**
     * Переопределить, если хочется доп логики после запрашивания адреса.
     *
     * @param Nutgram $bot
     * @return void
     */
    protected function internalAskAddress(Nutgram $bot)
    {

    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAddressAnswer(Nutgram $bot): void
    {
        // когда адрес или координаты в сообщение
        if ($bot->message()->text != "") {
            $address = $bot->message()->text;
            $address = trim($address);

            if ($this->getCoordinateService()->isCoordinates($address)) {
                $coordinates = $this->getCoordinateService()->parseCoordinates($address);
                if (!$coordinates) {
                    $this->askAddress($bot, 'Некорректные переданные координаты. Пожалуйста попробуйте ввести другие координаты.');
                    return;
                }
                try {
                    $locatedAddress = $this->getVendorStorageService()->getAddressByCoordinates(floatval($coordinates['latitude']), floatval($coordinates['longitude']));
                } catch (AddressNotFoundException $e) {
                    $this->askAddress($bot, 'Адреса по переданным координатам не найден. Пожалуйста попробуйте ввести другие координаты.');
                    return;
                }
            } else {
                try {
                    $locatedAddress = $this->getVendorStorageService()->getAddressByAddressString($address);
                } catch (AddressNotFoundException|EmptyAddressException $e) {
                    $this->askAddress($bot, 'Переданного адреса не найдено. Пожалуйста попробуйте ввести более подробно.');
                    return;
                }
            }
            $this->askToConfirmTheirAddress($bot, $locatedAddress);
            return;
        }

        // когда передача координат через телеграм
        if (!is_null($bot->message()->location)) {
            $latitude = $bot->message()->location->latitude;
            $longitude = $bot->message()->location->longitude;
            if (!$latitude || !$longitude) {
                $this->askAddress($bot, "Не удалось получить координаты из геолокации");
                return;
            }

            try {
                $locatedAddress = $this->getVendorStorageService()->getAddressByCoordinates($latitude, $longitude);
            } catch (AddressNotFoundException $e) {
                $this->askAddress($bot, 'Адреса по переданным координатам не найден. Пожалуйста попробуйте ввести другие координаты.');
                return;
            }
            $this->askToConfirmTheirAddress($bot, $locatedAddress);
            return;
        }

        $this->askAddress($bot);
    }

    /**
     * Просит уточнить является ли найденный адрес верным.
     *
     * @param Nutgram $bot
     * @param Address $address
     * @return void
     * @throws InvalidArgumentException
     */
    public function askToConfirmTheirAddress(Nutgram $bot, Address $address): void
    {
        $this->clearButtons();

        $this->addButtonRow(
            InlineKeyboardButton::make(
                text: "Подтвердить",
                callback_data: "@handleAskToConfirmTheirAddress"
            )
        );

        $bot->sendMessage(
            "Определен адрес: {$address->getAddress()}"
        );

        $bot->sendLocation(
            $address->getLatitude(),
            $address->getLongitude(),
            $bot->message()->chat->id
        );

        $this->address = $address;

        $this->orNext('handleAddressAnswer');
        $this->menuText(
            "Подтвердите адрес или отправьте новый адрес в любом удобном, из следующих форматов:\n
– Поделитесь вашей геолоĸацией (отправьте мне в ответ вашу геолокацию)
– Точный адрес в формате: садовое товарищество Зелёный Бор-1, д. 6, городской
округ Пушĸинсĸий, Мосĸовсĸая область.
– Отправьте точные ĸоординаты в формате: 55.967467, 37.870123"
        );

        $this->showMenu(reopen: true);
    }

    public function handleAskToConfirmTheirAddress(Nutgram $bot)
    {
        $this->internalHandleAddressAnswer($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     */
    protected function internalHandleAddressAnswer(Nutgram $bot)
    {
    }

    /**
     * Сохраняем жалобу о происшествие.
     *
     * @param $complaintMessage
     * @param string|null $clientID
     * @return Complaint|null
     */
    protected function saveComplaint($complaintMessage, ?string $clientID = null): ?Complaint
    {
        try {
            $complaint = $this->getComplaintService()->createComplaint($complaintMessage, $clientID);
        } catch (FailedToCreateComplaintException $e) {
            // TODO:: реализация на не запланированное поведение
        }

        return $complaint;
    }

    /**
     * Проверяем что сообщение является строкой и целым числом.
     *
     * @param string $value
     * @return string|null
     */
    protected function validateIntAnswer(string $value): ?string
    {
        $value = trim($value);

        $input = [
            'value' => $value,
        ];

        $rules = [
            'value' => 'required|string|numeric',
        ];

        $messages = [
            'value.required' => 'Введите число.',
            'value.numeric' => 'Строка должна быть числом.',
        ];

        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return null;
    }

    /**
     * Валидирует скидку относительно текущей цены.
     * Минимальная скидка - 0.1%. Максимальная - 50%.
     *
     * @param int $currentPrice
     * @param int $discountPrice
     * @return string|null
     */
    protected function validateDiscountNumber(int $currentPrice, int $discountPrice): ?string
    {
        $maxDiscount = (int)ceil($currentPrice * 0.5);
        $minDiscount = (int)ceil($currentPrice * 0.001);
        $input = [
            'discountPrice' => $discountPrice,
        ];

        $rules = [
            'discountPrice' => "numeric|min:$minDiscount|max:$maxDiscount",
        ];

        $messages = [
            'discountPrice' => "Минимальная и максимальная сумма скидки должна быть от $minDiscount до $maxDiscount",
        ];

        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return null;
    }

    /**
     * Проверка, что количество материала является целым числом и не меньше одного.
     *
     * @param string $value
     * @return string
     */
    protected function validateQuantityAnswer(string $value): string
    {
        $value = trim($value);

        $input = [
            'value' => $value,
        ];

        $rules = [
            'value' => 'required|numeric|integer|min:1',
        ];

        $messages = [
            'value.numeric' => 'Введите количество числом.',
            'value.integer' => 'Введите целое число.',
            'value.min' => 'Количество не должно быть меньше 1.',
        ];

        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return '';
    }

    /**
     * Проверяем что сообщение является строкой и одним словом.
     *
     * @param string $value
     * @return string
     */
    protected function validateSingleStringAnswer(string $value): string
    {
        $value = trim($value);

        $input = [
            'value' => $value,
        ];

        $rules = [
            'value' => 'required|string',
        ];

        $validator = Validator::make($input, $rules, attributes: ['value' => 'Пожалуйста, введите строку.']);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $r = explode(" ", $value);
        if (count($r) > 1) {
            return "Пожалуйста, введите ответ одним словом.";
        }

        return '';
    }

    /**
     * Валидируем и сохраняем файл.
     * Возвращает путь до файла или null. если возвращается null, то ошибка проставляется автоматически.
     *
     * Вы должны сами руками удалить файл после использования.
     *
     * @param Nutgram $bot
     * @return string|null
     * @throws InvalidArgumentException
     */
    protected function saveFile(Nutgram $bot): ?string
    {
        $file = $bot->message()->document;
        if (!($file instanceof Document)) {
            $this->menuText('Пожалуйста отправьте файл');
            $this->showMenu(reopen: true);

            return null;
        }

        $fileInfo = $bot->getFile($file->file_id);

        $uuid = Str::uuid();
        $storageFilePath = "app/telegram_files/" . "$uuid.xlsx";

        if (!$bot->downloadFileToDisk($fileInfo, $storageFilePath)) {
            $this->menuText('Ошибка сохранения файла, попробуйте еще раз');
            $this->showMenu(reopen: true);

            return null;
        }

        $err = $this->validateFileInfo($storageFilePath);
        if ($err) {
            $this->menuText($err);
            $this->showMenu(reopen: true);
            return null;
        }

        if (!Storage::exists($storageFilePath)) {
            $this->menuText('Ошибка загрузки файла');
            $this->showMenu(reopen: true);

            return null;
        }

        return $storageFilePath;
    }


    /**
     * @param string|null $value
     * @param string $valueName Имя значения которое выведется в ошибке.
     * @return string|null
     */
    public function validateCustomPercentDiscount(?string $value, string $valueName = 'скидка'): ?string
    {
        if (is_null($value)) {
            return "Значение не может быть пустым.";
        }

        $value = trim($value);

        $input = [
            $valueName => $value,
        ];

        $rules = [
            $valueName => 'required|numeric|min:0.1|max:100',
        ];

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return null;
    }

    /**
     * Проверяем что файл excel и не больше заданного размера.
     *
     * @param string $storageFilePath
     * @return string|null
     */
    protected function validateFileInfo(string $storageFilePath): ?string
    {
        $fileContent = Storage::get($storageFilePath);
        if (is_null($fileContent)) {
            return "Ошибка при загрузке файла. Попробуйте еще раз.";
        }

        $size = Storage::size($storageFilePath);
        if (5242880 < $size) { // 5mb
            return "Файл слишком большой.";
        }

        $fileMimeTypes = Storage::mimeType($storageFilePath);
        $allowedMimeTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'application/vnd.ms-excel.template.macroEnabled.12',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'application/vnd.ms-excel.addin.macroEnabled.12',
        ];

        if (!in_array($fileMimeTypes, $allowedMimeTypes)) {
            return "File must be an Excel format.";
        }

        return null;
    }

    /**
     * Создаем сообщение с ошибками из excel файла
     *
     * @param $errors
     * @return string
     */
    protected function makeExcelErrorMessage($errors): string
    {
        $res = "";

        foreach ($errors as $errorArr) {
            $errStr = "Ошибки на строке {$errorArr['row']}\n";
            foreach ($errorArr as $err) {
                if (!is_array($err)) {
                    continue;
                }
                foreach ($err as $e) {
                    $errStr .= "{$e}\n";
                }
            }

            $res .= $errStr . "\n\n";
        }

        return $res;
    }

    function makeErrMsgFromArray($data): string
    {
        $res = "";

        foreach ($data as $err) {
            $res .= $err . "\n";
        }

        return $res;
    }

    /**
     * Перенаправляет пользователя в меню, в зависимости от его роли.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function backToMenu(Nutgram $bot): void
    {
        $this->end();
        if (Auth::user()->getTable() == 'vendors') {
            VendorMenu::begin($bot);
        }
        ClientMenu::begin($bot);
    }

    protected function getMaterialService(): MaterialService
    {
        return App::get(MaterialService::class);
    }

    protected function getMaterialQuestionsService(): MaterialQuestionsService
    {
        return App::get(MaterialQuestionsService::class);
    }

    protected function getMaterialQuestionAnswersService(): MaterialQuestionAnswersService
    {
        return App::get(MaterialQuestionAnswersService::class);
    }

    protected function getOrderService(): OrderService
    {
        return App::get(OrderService::class);
    }

    protected function getClientService(): ClientService
    {
        return App::get(ClientService::class);
    }

    protected function getVendorService(): VendorService
    {
        return App::get(VendorService::class);
    }

    protected function getComplaintService(): ComplaintService
    {
        return App::get(ComplaintService::class);
    }

    protected function getExcelService(): ExcelService
    {
        return App::get(ExcelService::class);
    }

    protected function getVendorStorageService(): VendorStorageService
    {
        return App::get(VendorStorageService::class);
    }

    protected function getOrderRequestService(): OrderRequestService
    {
        return App::get(OrderRequestService::class);
    }

    protected function getDeliveryService(): DeliveryService
    {
        return App::get(DeliveryService::class);
    }

    protected function getCoordinateService(): CoordinateService
    {
        return App::get(CoordinateService::class);
    }
}
