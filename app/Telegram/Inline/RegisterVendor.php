<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Enums\VendorType;
use App\Exceptions\CompanyNotFoundException;
use App\Exceptions\FailedToCreateVendorException;
use App\Exceptions\IndividualNotFoundException;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class RegisterVendor extends InlineMenuBase
{
    public string $fullName = '';
    public ?string $companyINN = null;

    public Vendor $vendor;

    /**
     * Точки и материалы из ексель файла для сохранения при создание вендора.
     *
     * @var array
     */
    public array $storagesWithMaterialsToCreate = [];


    public function __construct()
    {
        parent::__construct();
    }

    public function start(Nutgram $bot): void
    {
        $this->menuText('Прошу ознакомиться с договором и политикой в области обработки, хранения персональных данных.');

        $this->addButtonRow(InlineKeyboardButton::make('Ознакомиться', url: 'https://www.google.ru/'));
        $this->addButtonRow(InlineKeyboardButton::make('Я ознакомился', callback_data: "@handleAcknowledgement"));

        $this->showMenu();

    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleAcknowledgement(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->menuText('Представьтесь пожалуйста, ответьте на это сообщение в следующем формате: Фамилия Имя Отчество');
        $this->orNext('handleUserFullName');
        $this->showMenu(reopen: true);

    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleUserFullName(Nutgram $bot): void
    {
        $fullName = trim($bot->message()->text);

        $err = $this->validateFullName($fullName);
        if (!is_null($err)) {
            $bot->sendMessage($err);
            return;
        }

        $this->fullName = $fullName;
        $this->askIsIndividualOrCompany($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function askIsIndividualOrCompany(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText('Какая форма организации юридического лица у Вашей компании, выберите один вариант из списка:');
        $this->addButtonRow(InlineKeyboardButton::make("ООО и другие(АО/ЗАО и т.п.)", callback_data: "@handleCompanyChoice"));
        $this->addButtonRow(InlineKeyboardButton::make("Индивидуальный предприниматель", callback_data: "@handleIndividualChoice"));

        $this->showMenu(reopen: true);
    }

    /**
     * Регистрация вендора как компании ООО
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCompanyChoice(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->menuText('Отправьте ИНН юридического лица(компании) которую вы представляйте, только номер. Ответьте в следующем формате - 5044124771');
        $this->orNext('handleCompanyINN');

        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCompanyINN(Nutgram $bot): void
    {
        $companyINN = $bot->message()->text;

        if (!$this->validateINN($bot, $companyINN, false)) {
            $this->handleCompanyChoice($bot);
            return;
        }

        $this->companyINN = $companyINN;

        if ($this->getVendorService()->isVendorINNExists($companyINN)) {
            $this->handleCompanyINNAlreadyExists($bot, $companyINN);
            return;
        }

        try {
            $vendor = $this->getVendorService()->makeVendorFromCompanyData($companyINN);
        } catch (CompanyNotFoundException $e) {
            $bot->sendMessage(text: 'Такая компания не найдена. Ваша компания должна быть активна.');
            $this->handleCompanyChoice($bot);
            return;
        }
        $this->vendor = $vendor;
        $this->vendor->type = VendorType::Company->value;

        $this->askCompanyConfirmation($bot);
    }

    public function askCompanyConfirmation(Nutgram $bot)
    {
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Подтверждаю",
                callback_data: "@askStoragesByFile"
            )
        );

        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Ввести другой ИНН",
                callback_data: "@handleCompanyChoice"
            )
        );

        $text = "Это данные вашей компании?\n";
        $text .= "ИНН: {$this->vendor->inn}\n";
        $text .= "Название компании: {$this->vendor->company_name}\n";
        $text .= "ОГРН: {$this->vendor->ogrn}\n";
        if (!is_null($this->vendor->address)) {
            $text .= "Адресс:{$this->vendor->address}";
        }
        $this->menuText($text);

        $this->showMenu(reopen: true);
    }

    /**
     * Отправляем пример excel file
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function askStoragesByFile(Nutgram $bot)
    {
        $this->clearButtons();

        $excelService = $this->getExcelService();

        $vendor = new Vendor();
        $vendor->company_name = $this->vendor['company_name'];
        $vendor->inn = $this->companyINN;

        $binaryHTTPResponse = $excelService->makeStoragesExportFileToCreate($vendor);

        $filePath = $binaryHTTPResponse->getFile()->getPathname();
        $fileHandle = fopen($filePath, 'r');

        $dateTime = Carbon::now()->format('Y-m-d_H-i-s.u');
        $bot->sendDocument(InputFile::make($fileHandle, "точки_$dateTime.xlsx"), $bot->chatId());

        // удаляем файл после отправки руками
        unlink($binaryHTTPResponse->getFile()->getPathname());

        $this->orNext('handleCreateStoragesByFile');

        $this->menuText(
            'Я отправил Вам файл.
Для работы в нашем сервисе, внесите данные в этот файл и отправить его обратно в эту переписку.
Нужно указать следующую информацию:
– Точки(базы) откуда будет осуществляться доставка до клиента.
– Наличие материалов на каждой базе.
– Стоимость материалов(в наличии) и стоимость доставки.
Как это сделать? Подробную инструкцию в картинках, я прислал выше вместе с файлом. Так же, она доступна по этой ссылке:

truck-go.ru/bloha69
Пришлите в ответ на это сообщение, заполненный файл.'
        );

        $this->showMenu();
    }


    /**
     * Принимаем и валидируем excel file о точках от поставщика
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCreateStoragesByFile(Nutgram $bot): void
    {
        $filePath = $this->saveFile($bot);
        if (!$filePath) {
            return;
        }

        $data = $this->getExcelService()->parseStoragesFile($filePath);
        Storage::delete($filePath);

        $errors = $data->get('errors');
        if ($errors) {
            $msg = $this->makeExcelErrorMessage($errors);
            $msg .= "Поправьте ошибки и снова отправьте файл";
            $bot->sendMessage(
                $msg
            );

            return;
        }

        $this->storagesWithMaterialsToCreate = $data->get('storages_with_materials_to_create');


        $this->finishVendorRegistration($bot);
    }

    /**
     * Спрашиваем работает ли поставщик через ЕДО.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function askEDM()
    {
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Да, работает через ЭДО",
                callback_data: "@handleComplaint"
            )
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Нет, документы на бумаге",
                callback_data: "@handleHandleWorkThroughEDM"
            )
        );

        $this->menuText(
            "Финальный этап перед активацией акаунта.
Уточните, ваша компания работает через ЭДО?"
        );
        $this->showMenu();
    }

    public function handleHandleWorkThroughEDM(Nutgram $bot)
    {

    }

    /**
     * @param Nutgram $bot
     * @param $inn
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCompanyINNAlreadyExists(Nutgram $bot, $inn): void
    {
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Хотите сменить ответственного?",
                callback_data: "@handleMakeComplaint"
            )
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Вы единственный лица, и ранее ни вы, \n ни компания не работали с сервисом TruckGO?",
                callback_data: "@handleComplaint"
            )
        );
        $this->addButtonRow(InlineKeyboardButton::make("Произошла другая ошибка?", callback_data: "@handleComplaint"));

        $this->menuText($inn);

        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $complaint
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleComplaint(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();
        if (!$complaint) {
            $bot->sendMessage('Некорректная жалоба');
            $this->handleCompanyINNAlreadyExists($bot, $this->companyINN);
            return;
        }

        $complaint .= " company_inn=$this->companyINN";

        $complaint = $this->saveComplaint($complaint);
        if (!$complaint->exists()) {
            $bot->sendMessage('Не удалось сохранить жалобу');
            $this->handleCompanyINNAlreadyExists($bot, $this->companyINN);
            return;
        }

        $this->menuText(
            "Спасибо, номер вашего обращения $complaint->id\n
Мы обязательно сделаем все в течении двух рабочих дней."
        );

        $this->clearButtons();

        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleIndividualChoice(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->menuText('Отправьте ИНН индивидуального предпринимателя, только номер. Ответьте в следующем формате - 504213008372');
        $this->orNext('handleIndividualINN');

        $this->showMenu(reopen: true);
    }

    /**
     * Регистрация вендора как ИП.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleIndividualINN(Nutgram $bot): void
    {
        $inn = $bot->message()->text;

        if (!$this->validateINN($bot, $inn, true)) {
            $this->handleIndividualChoice($bot);
            return;
        }

        $this->companyINN = $inn;

        if ($this->getVendorService()->isVendorINNExists($inn)) {
            $this->handleCompanyINNAlreadyExists($bot, $inn);
            return;
        }

        try {
            $vendor = $this->getVendorService()->makeVendorFromIndividualData($inn);
        } catch (IndividualNotFoundException $e) {
            $bot->sendMessage(text: "Ип с инн $inn не найдена. Помните что ваша компания должна быть активна.");
            $this->handleIndividualChoice($bot);
            return;
        }
        $this->vendor = $vendor;
        $this->vendor->type = VendorType::Individual->value;

        $this->askCompanyConfirmation($bot);
    }


    /**
     * Завершаем регистрацию поставщика.
     * Добавляем поставщика, склады и материалы в БД.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function finishVendorRegistration(Nutgram $bot): void
    {
        try {
            $this->vendor->name = $this->fullName;
            $this->vendor->telegram_chat_id = $bot->chatId();
            $vendor = $this->getVendorService()->createVendorWithStoragesAndMaterials($this->vendor, $this->storagesWithMaterialsToCreate);
        } catch (FailedToCreateVendorException $e) {
            // TODO:: реализация на не запланированное поведение
            $bot->sendMessage(
                'Сожалеем, произошла ошибка при завершение регистрации.
            Пожалуйста попробуйте позже или напишите нам.'
            );
            return;
        }

        Auth::login($vendor);
        $bot->sendMessage(
            text: "Вы успешно зарегистрированы"
        );
        $this->end();
        VendorMenu::begin($bot);
    }


    /**
     * @param Nutgram $bot
     * @param string $inn
     * @param bool $isIndividual
     * @return bool
     */
    protected function validateINN(Nutgram $bot, string $inn, bool $isIndividual): bool
    {
        $count = 10;
        if ($isIndividual) {
            $count = 12;
        }

        if (!$inn) {
            $bot->sendMessage('Некорректный ИНН');
            return false;
        }


        if (strlen($inn) != $count) {
            $bot->sendMessage("ИНН должен иметь $count цифр");
            return false;
        }


        if (!is_numeric($inn)) {
            $bot->sendMessage('ИНН должен быть цифрой');
            return false;
        }

        return true;
    }


    /**
     * Проверяем что Имя Фамилия Отчество существуют и заданы в правильном формате.
     *
     * @param string $string
     * @return string|null
     */
    protected function validateFullName(string $string): ?string
    {
        $input = [
            'value' => $string,
        ];

        $rules = [
            'value' => 'required|string|min:8|max:100|regex:/^[a-zа-яё\s]+$/iu',
        ];

        $messages = [
            'value.required' => 'Введите Ф.И.О',
            'value.min' => 'Слишком короткое Ф.И.О',
            'value.max' => 'Слишком длинное Ф.И.О!',
            'value.regex' => 'Ваше Ф.И.О должно содержать только буквы!',
        ];

        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $arr = preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
        if (count($arr) < 3) {
            return 'Пожалуйста, введите Ф.И.О!';

        }

        if (count($arr) > 3) {
            return 'Пожалуйста, введите только Ф.И.О!';

        }

        return null;
    }
}
