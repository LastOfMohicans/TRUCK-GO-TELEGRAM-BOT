<?php
declare(strict_types=1);

namespace App\Telegram\Inline;

use App\Enums\Permissions;
use App\Exceptions\FailedCancelDiscountRequestException;
use App\Exceptions\FailedCancelOrderRequestException;
use App\Exceptions\FailedMakeDiscountForOrderException;
use App\Exceptions\FailedMakeOfferForOrderException;
use App\Exceptions\FailedUpdateVendorCompanyData;
use App\Models\Delivery;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Vendor;
use App\Models\VendorStorage;
use App\Services\OrderRequestService;
use App\Services\OrderService;
use App\StateMachines\OrderRequestStatusStateMachine;
use App\StateMachines\OrderStatusStateMachine;
use App\Telegram\Traits\Listable;
use App\Telegram\Traits\OrderRequestDateTimeSelectable;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class VendorMenu extends InlineMenuBase
{
    use Listable, OrderRequestDateTimeSelectable;

    protected Vendor $vendor;

    protected OrderService $orderService;
    protected OrderRequestService $orderRequestService;

    protected array $storageIDsToActivate = [];

    protected array $storageIDsToDeactivate = [];

    protected array $storageIDToManager = [];

    public int $fileMessageID;

    public string $currentPage;

    public ?float $discountPercents = null;

    /**
     * Сюда сохраняем идентификатор отклика, которому мы дадим скидку после ее выбора.
     *
     * @var string
     */
    public string $orderRequestIDToGiveDiscount;

    public function __construct(OrderService $orderService, OrderRequestService $orderRequestService)
    {
        parent::__construct();

        $this->orderService = $orderService;
        $this->orderRequestService = $orderRequestService;
        $this->vendor = Auth::user();
    }

    public function start(Nutgram $bot, bool $reopen = false)
    {
        $this->clearButtons();


        $this->addButtonRow(InlineKeyboardButton::make("Начать прием заказов", callback_data: "@handleStartAcceptingOrders"));
        if (!is_null($this->getVendorStorageService()->firstActiveVendorStorage($this->vendor->id))) {
            $this->addButtonRow(InlineKeyboardButton::make("Завершить прием заказов", callback_data: "@handleCompleteAcceptingOrders"));
        }
        $this->addButtonRow(InlineKeyboardButton::make("Заказы", callback_data: "@handleOrders"));
        $this->addButtonRow(InlineKeyboardButton::make("Архив заказов", callback_data: "@handleArchiveOrders"));

        if ($this->vendor->hasPermissionTo(Permissions::EditProfile->value)) {
            $this->addButtonRow(InlineKeyboardButton::make("Редактировать профиль", callback_data: "@handleEditProfile"));
        }

        $this->addButtonRow(InlineKeyboardButton::make("Поддержка", callback_data: "@test"));

        $this->menuText(
            'Добро пожаловать!
Выберите действие из списка:'
        );
        $this->showMenu(reopen: $reopen);
    }

    /**
     * Функция начинает приём заказов.
     *
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleStartAcceptingOrders(Nutgram $bot, string $page = '1'): void
    {
        $this->clearButtons();

        $vendorID = $this->vendor->id;

        $inactiveVendorStorages = $this->getVendorStorageService()->getInactiveVendorStoragesPaginate($vendorID, (int)$page);

        if ($inactiveVendorStorages->isEmpty()) {
            $this->menuText('У вас нет неактивных складов!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }
        $buttons = [];
        /** @var VendorStorage $storage */
        foreach ($inactiveVendorStorages as $storage) {
            $storageID = $storage->id;
            $address = $storage->address;
            if (array_key_exists($storageID, $this->storageIDsToActivate)) {
                $buttons[] = $this->addButtonRow(InlineKeyboardButton::make(" ✅" . $address, callback_data: $storageID . "@handleDeleteActiveStorage"));
                continue;
            }
            $this->addButtonRow(InlineKeyboardButton::make($address, callback_data: $storageID . "@handleSelectVendorStorage"));
        }

        if (count($buttons) >= 1) {
            $this->addButtonRow(InlineKeyboardButton::make('Подтверждаю', callback_data: "@handleActivateVendorStorages"));
        }
        $this->menuText('Ваши неактивные склады!');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->makeList($inactiveVendorStorages, 'handleStartAcceptingOrders');
        $this->showMenu();
    }

    /**
     * Показывает архивные заказы в статусе completed.
     *
     * @param Nutgram $bot
     * @param $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleArchiveOrders(Nutgram $bot, $page = 1): void
    {
        $this->clearButtons();

        $archiveOrdersRequest = $this->getOrderRequestService()->getCompletedOrderRequestsPaginate(
            $this->vendor->id,
            (int)$page,
            1
        );

        if ($archiveOrdersRequest->isEmpty()) {
            $this->menuText('У вас нет заказов в архиве!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }

        /** @var OrderRequest $orderRequest */
        $orderRequest = $archiveOrdersRequest->items()[0];
        $material = $this->getMaterialService()->firstMaterial($orderRequest->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($orderRequest->id);

        $msg = $this->makeOrderMessage($orderRequest, $material, $delivery);
        $this->menuText('Ваши заказы в архиве: ' . PHP_EOL . $msg);

        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->makeList($archiveOrdersRequest, 'handleArchiveOrders');
        $this->showMenu();
    }

    /**
     * Создаем сообщения для показа заказа.
     *
     * @param OrderRequest $orderRequest
     * @param Material $material
     * @param Delivery $delivery
     * @return string
     */
    protected function makeOrderMessage(
        OrderRequest $orderRequest,
        Material $material,
        Delivery $delivery,
    ): string {
        $msg = "По заказу №: $orderRequest->id" . PHP_EOL;
        $msg .= "Статус заказа: {$orderRequest->status}" . PHP_EOL;
        $msg .= "Дата доставки: {$delivery->wanted_delivery_window_start}:{$delivery->wanted_delivery_window_end}" . PHP_EOL;
        $msg .= "Адрес доставки: {$delivery->address}" . PHP_EOL;
        $msg .= "Товар: {$material->full_name}" . PHP_EOL;

        return $msg;
    }

    /**
     * Функция удаляет активный склад поставщика.
     *
     * @param Nutgram $bot
     * @param int $storageID
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleDeleteActiveStorage(Nutgram $bot, int $storageID): void
    {
        unset($this->storageIDsToActivate[$storageID]);
        $this->handleStartAcceptingOrders($bot);
    }

    /**
     * Функция активирует все склады поставщика.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleActivateVendorStorages(Nutgram $bot): void
    {
        $activeVendors = $this->storageIDsToActivate;
        $vendorID = $this->vendor->id;
        $storages = array_keys($activeVendors);
        $storageAddresses = implode(', ', $activeVendors);

        $this->getVendorStorageService()->activeVendorStoragesOrderSearch($vendorID, $storages);

        unset($this->storageIDsToActivate);
        $this->clearButtons();

        $this->menuText("Отлично, вы активировали прием заказов для точек: $storageAddresses\n Для отслеживания новых заĸазов перейдите в раздел 'Новые Заĸазы'");
        unset($activeVendors);
        $this->addButtonRow(InlineKeyboardButton::make('Новые заказы', callback_data: "@handleShowNewRequestByOne"));
        $this->addButtonRow(InlineKeyboardButton::make('Назад', callback_data: "@start"));
        $this->showMenu();
    }

    /**
     * Функция управляет выбранной кнопкой.
     *
     * @param Nutgram $bot
     * @param int $storageID
     * @return void
     */
    protected function handleSelectVendorStorage(Nutgram $bot, int $storageID): void
    {
        $vendorStorage = $this->getVendorStorage($storageID);
        $this->storageIDsToActivate[$vendorStorage->id] = $vendorStorage->address;
        $this->handleStartAcceptingOrders($bot);
    }

    /**
     * Функция заканчивает приём заказов.
     *
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCompleteAcceptingOrders(Nutgram $bot, string $page = '1'): void
    {
        $this->clearButtons();

        $vendorID = $this->vendor->id;

        $activeVendorStorages = $this->getVendorStorageService()->getActiveVendorStoragePaginate($vendorID, (int)$page);

        if ($activeVendorStorages->isEmpty()) {
            $this->menuText('У вас нет активных складов!');
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
            $this->showMenu();
            return;
        }
        $buttons = [];
        /** @var VendorStorage $storage */
        foreach ($activeVendorStorages as $storage) {
            $storageID = $storage->id;
            $address = $storage->address;
            if (array_key_exists($storageID, $this->storageIDsToDeactivate)) {
                $buttons[] = $this->addButtonRow(InlineKeyboardButton::make(" ✅" . $address, callback_data: $storageID . "@handleRemoveActiveStorage"));
                continue;
            }
            $this->addButtonRow(InlineKeyboardButton::make($address, callback_data: $storageID . "@handleSelectActiveVendorStorage"));
        }

        if (count($buttons) >= 1) {
            $this->addButtonRow(InlineKeyboardButton::make('Подтверждаю', callback_data: "@handleDeactivateVendorStorages"));
        }
        $this->menuText('Ваши активные склады!');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->makeList($activeVendorStorages, 'handleCompleteAcceptingOrders');
        $this->showMenu();
    }

    /**
     * Функция удаляет неактивный склад поставщика.
     *
     * @param Nutgram $bot
     * @param int $storageID
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleRemoveActiveStorage(Nutgram $bot, int $storageID): void
    {
        $this->clearButtons();
        unset($this->storageIDsToDeactivate[$storageID]);
        $this->handleCompleteAcceptingOrders($bot);
    }

    /**
     * Функция управляет выбранной кнопкой.
     *
     * @param Nutgram $bot
     * @param int $storageID
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleSelectActiveVendorStorage(Nutgram $bot, int $storageID): void
    {
        $vendorStorage = $this->getVendorStorage($storageID);
        $this->storageIDsToDeactivate[$vendorStorage->id] = $vendorStorage->address;
        $this->handleCompleteAcceptingOrders($bot);
    }

    /**
     * Функция деактивирует все склады поставщика.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleDeactivateVendorStorages(): void
    {
        $inActiveVendors = $this->storageIDsToDeactivate;
        $vendorID = $this->vendor->id;
        $storages = array_keys($inActiveVendors);
        $storageAdresses = implode(', ', $inActiveVendors);

        $this->getVendorStorageService()->deactivateVendorStoragesOrderSearch($vendorID, $storages);


        unset($this->storageIDsToDeactivate);
        $this->clearButtons();

        $this->menuText("Отлично, вы деаĸтивировали прием заĸазов для точеĸ: $storageAdresses\n Для отслеживания новых заĸазов перейдите в раздел 'Новые Заĸазы'");
        unset($inActiveVendors);
        $this->addButtonRow(InlineKeyboardButton::make('Новые заказы', callback_data: "@handleShowNewRequestByOne"));
        $this->addButtonRow(InlineKeyboardButton::make('Вернуться в меню', callback_data: "@start"));
        $this->showMenu();
    }

    function handleEditProfile(Nutgram $bot)
    {
        $this->clearButtons();

        $this->addButtonRow(InlineKeyboardButton::make("Базы/Материалы/Цены", callback_data: "@askEditStoragesByFile"));
        $this->addButtonRow(InlineKeyboardButton::make("Данные по компании", callback_data: "@handleShowVendorProfile"));
        $this->addButtonRow(InlineKeyboardButton::make("Банковские реквизиты", callback_data: "@test"));
        $this->addButtonRow(InlineKeyboardButton::make("Редактировать штат", callback_data: "@handleEditStaff"));

        $this->addBackToStartButton();

        $this->menuText(
            'Добро пожаловать!
Выберите действие из списка:'
        );

        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function askEditStoragesByFile(Nutgram $bot): void
    {
        $this->clearButtons();

        $excelService = $this->getExcelService();

        $binaryHTTPResponse = $excelService->makeStoragesExportFileToEdit($this->vendor);

        $filePath = $binaryHTTPResponse->getFile()->getPathname();
        $fileHandle = fopen($filePath, 'r');

        $dateTime = Carbon::now()->format('Y-m-d_H-i-s.u');
        $m = $bot->sendDocument(InputFile::make($fileHandle, "storages_edit_{$dateTime}.xlsx"), $bot->chatId());
        $this->fileMessageID = $m->message_id;

        // удаляем файл после отправки руками
        unlink($binaryHTTPResponse->getFile()->getPathname());

        $this->addButtonRow(InlineKeyboardButton::make("Вернуться в меню", callback_data: "@backWithFileDelete"));
        $this->orNext('handeEditStoragesByFile');

        $this->menuText(
            'Я отправил Вам файл.
Для работы в нашем сервисе, внесите данные в этот файл и отправить его обратно в эту переписĸу.
Нужно уĸазать следующую информацию:
– Точĸи(базы) отĸуда будет осуществляться доставĸа до ĸлиента.
– Наличие материалов на ĸаждой базе.
– Стоимость материалов(в наличии) и стоимость доставĸи.
Каĸ это сделать? Подробную инструĸцию в ĸартинĸах, я прислал выше вместе с файлом. Таĸ же, она доступна по этой ссылĸе:
trackgo.ru/bloha69
Пришлите в ответ на это сообщение, заполненный файл.'
        );

        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handleShowVendorProfile(Nutgram $bot): void
    {
        $this->clearButtons();

        $text = "Данные о компании.\n";
        $text .= "ИНН: {$this->vendor->inn}\n";
        $text .= "КПП: {$this->vendor->kpp}\n";
        $text .= "ОГРН: {$this->vendor->ogrn}\n";
        $text .= "Адрес: {$this->vendor->address}\n";
        $text .= "Название компании: {$this->vendor->company_name}\n";

        $this->menuText($text);
        $this->addButtonRow(InlineKeyboardButton::make("Обновить данные", callback_data: "@handleUpdateVendorProfile"));
        $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleEditProfile"));
        $this->showMenu();
    }

    /**
     * Функция добавляет менеджеров и водителей в склад.
     *
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handleEditStaff(Nutgram $bot): void
    {
        $this->clearButtons();

        $text = "\nКого добавить?\n";

        $this->menuText($text);

        $this->addButtonRow(InlineKeyboardButton::make("Менеджера", callback_data: "@handleAddManager"));
        $this->addButtonRow(InlineKeyboardButton::make("Машину", callback_data: "@handleAddDriver"));

        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: "@handleEditProfile"));

        $this->showMenu();
    }

    /**
     * Функция добавляет менеджера.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handleAddManager(Nutgram $bot, string $page = '1'): void
    {
        $this->currentPage = $page;

        $this->clearButtons();

        $vendorID = $this->vendor->id;

        $vendorStorages = $this->getVendorStorageService()->getActiveVendorStoragePaginate($vendorID, (int)$page);

        if ($vendorStorages->isEmpty()) {
            $this->menuText("Сначала создайте хоть 1 склад.");
            $this->addButtonRow(InlineKeyboardButton::make("Добавить склады", callback_data: "@askEditStoragesByFile"));
            $this->showMenu();
            return;
        }

        $this->addButtonRow(InlineKeyboardButton::make('Ко всем', callback_data: '@handleSelectAllStorages'));

        $buttons = [];
        /** @var VendorStorage $storage */
        foreach ($vendorStorages as $storage) {
            $storageID = $storage->id;
            $address = $storage->address;

            if (array_key_exists($storageID, $this->storageIDToManager)) {
                $buttons[] = $this->addButtonRow(InlineKeyboardButton::make(" ✅" . $address, callback_data: $storageID . "@handleUnassignManagetFromStorage"));
                continue;
            }

            $this->addButtonRow(InlineKeyboardButton::make($address, callback_data: $storageID . "@handleAssignManagerToStorage"));
        }

        if (count($buttons) >= 1) {
            $this->addButtonRow(InlineKeyboardButton::make('Готово', callback_data: "@handleCreateManager"));
        }

        $this->menuText('К заказам какого склада будет доступ у менеджера:');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@handleEditStaff'));

        $this->makeList($vendorStorages, 'handleAddManager');
        $this->showMenu();
    }

    /**
     * Присоединяем склад к менеджеру
     *
     * @param Nutgram $bot
     * @param string $storageID
     * @return void
     */
    public function handleAssignManagerToStorage(Nutgram $bot, string $storageID): void
    {
        if (!array_key_exists($storageID, $this->storageIDToManager)) {
            $this->storageIDToManager[$storageID] = true;
        }

        $this->handleAddManager($bot, $this->currentPage);
    }

    /**
     * Отсоединяем склад от менеджера.
     *
     * @param Nutgram $bot
     * @param string $storageID
     * @return void
     */
    public function handleUnassignManagetFromStorage(Nutgram $bot, string $storageID): void
    {
        if (array_key_exists($storageID, $this->storageIDToManager)) {
            unset($this->storageIDToManager[$storageID]);
        }

        $this->handleAddManager($bot, $this->currentPage);
    }

    /**
     * Функция назначает менеджера на выбранные склады.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handleCreateManager(Nutgram $bot): void
    {
        $this->clearButtons();

        if (empty($this->storageIDToManager)) {
            $this->menuText('Вы не выбрали склады!');
            $this->showMenu();
            return;
        }

        $this->menuText('Менеджер успешно назначен на выбранные склады!');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@handleEditStaff'));
        $this->showMenu();
    }

    /**
     * Функция выбирает все склады для менеджера.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function handleSelectAllStorages(Nutgram $bot): void
    {
        $vendorID = $this->vendor->id;
        $allStorages= $this->getVendorStorageService()->getStorageIDs($vendorID);

        $allSelected = count($this->storageIDToManager) === count($allStorages);

        if ($allSelected) {
            $this->storageIDToManager = [];
        } else {
            foreach ($allStorages as $storageID) {
                $this->storageIDToManager[$storageID] = true;
            }
        }

        $this->handleAddManager($bot);
    }

    /**
     * Функция добавляет водителя.
     *
     * @param Nutgram $bot
     * @return void
     */
    function handleAddDriver(Nutgram $bot): void
    {

    }


    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleUpdateVendorProfile(Nutgram $bot): void
    {
        try {
            $this->getVendorService()->updateVendorCompanyData($this->vendor);
        } catch (FailedUpdateVendorCompanyData $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }

        $this->handleShowVendorProfile($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handeEditStoragesByFile(Nutgram $bot): void
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

        try {
            $resp = $this->getVendorStorageService()->updateVendorStorages($this->vendor->id, $data);
        } catch (Exception $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }

        if (isset($resp['errors'])) {
            $msg = $this->makeErrMsgFromArray($resp['errors']);
            $msg .= "Поправьте ошибки и снова отправьте файл";
            $bot->sendMessage(
                $msg
            );
            return;
        }

        $bot->sendMessage('Данные по точкам успешно обновлены');

        $this->start($bot, true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handleOrders(Nutgram $bot): void
    {
        $this->clearButtons();

        $statusToCount = $this->orderRequestService->getUnseenRequestCountToStatus($this->vendor->id);
        $orderStatusToCount = $this->orderRequestService->getUnseenRequestCountToOrderStatus($this->vendor->id);

        $textCreated = "Новые заказы";
        if (isset($statusToCount[OrderRequestStatusStateMachine::CREATED])) {
            $textCreated .= "({$statusToCount[OrderRequestStatusStateMachine::CREATED]})";
        }
        $this->addButtonRow(InlineKeyboardButton::make($textCreated, callback_data: "1@handleShowNewRequestByOne"));

        $textClientWantDiscount = "Заказы на которые хотят скидку.";
        if (isset($statusToCount[OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT])) {
            $textClientWantDiscount .= "({$statusToCount[OrderRequestStatusStateMachine::CLIENT_WANT_DISCOUNT]})";
        }
        $this->addButtonRow(
            InlineKeyboardButton::make($textClientWantDiscount, callback_data: "1@handleShowDiscountRequestsByOne")
        );

        $textLoading = "К погрузке";
        if (isset($orderStatusToCount[OrderStatusStateMachine::LOADING])) {
            $textLoading .= "({$orderStatusToCount[OrderStatusStateMachine::LOADING]})";
        }
        $this->addButtonRow(InlineKeyboardButton::make($textLoading, callback_data: "@handleShowLoadingRequestsByOne"));

        $textOnTheWay = "В пути";
        if (isset($orderStatusToCount[OrderStatusStateMachine::ON_THE_WAY])) {
            $textOnTheWay .= "({$orderStatusToCount[OrderStatusStateMachine::ON_THE_WAY]})";
        }
        $this->addButtonRow(InlineKeyboardButton::make($textOnTheWay, callback_data: "@handleShowOnTheWayOrdersByOne"));

        $textWaitingDocuments = "Отгружены/не учтены";
        if (isset($statusToCount[OrderRequestStatusStateMachine::WAITING_DOCUMENTS])) {
            $textWaitingDocuments .= "({$statusToCount[OrderRequestStatusStateMachine::WAITING_DOCUMENTS]})";
        }
        $this->addButtonRow(
            InlineKeyboardButton::make($textWaitingDocuments, callback_data: "@handleShowWaitingDocumentsRequestsByOne")
        );
        $this->addBackToStartButton();

        $this->menuText('Выберите действие:');
        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    protected function handleShowNewRequestByOne(Nutgram $bot): void
    {
        $this->clearButtons();
        $orderRequest = $this->getOrderRequestService()->firstCreatedOrderRequest($this->vendor->id);
        if (is_null($orderRequest)) {
            $bot->sendMessage("Больше нет заказов для активных складов. Вернитесь позже.");
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: "@handleOrders"));
            $this->showMenu(reopen: true);
            return;
        }

        $order = $orderRequest->order;
        $vendorStorage = $this->getVendorStorageService()->firstVendorStorage($this->vendor->id, $orderRequest->vendor_storage_id);

        $msg = $this->makeNewRequestMessage(
            $orderRequest,
            $order,
            $order->delivery,
            $order->material,
            $vendorStorage
        );
        $bot->sendMessage($msg);
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "По своей цене",
                callback_data: "{$orderRequest->id}@handleMakeOfferForOrder"
            )
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(
                "По СРЦ",
                callback_data: "{$orderRequest->id}@handleMakeOfferForOrder"
            )
        );
        $this->addButtonRow(
            InlineKeyboardButton::make("Отказаться", callback_data: "{$orderRequest->id}@handleCancelOrder")
        );

        $this->menuText("Выберите действие:");
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: "@handleOrders"));

        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     * @throws InvalidArgumentException
     */
    function handleChooseDateBeforeMakeOfferForOrder(Nutgram $bot, string $orderRequestID): void
    {
        $this->askDate($bot);
    }


    /**
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleMakeOfferForOrder(Nutgram $bot, string $orderRequestID): void
    {
        try {
            $orderRequest = $this->getOrderRequestService()->makeOfferForOrder($this->vendor->id, $orderRequestID);
        } catch (FailedMakeOfferForOrderException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if (is_null($orderRequest)) {
            $this->handleShowNewRequestByOne($bot);
            return;
        }
        $bot->sendMessage(
            "Отклик по Заказу $orderRequest->order_id по своим ценам, отправлен клиенту.
Каĸ только клиент даст обратную связь, вы увидите это в разделе «Активные заказы»", $bot->chatId()
        );

        $this->handleShowNewRequestByOne($bot);
    }

    /**
     * @param Nutgram $bot
     * @param string $orderRequestID
     * @return void
     * @throws InvalidArgumentException
     */
    function handleCancelOrder(Nutgram $bot, string $orderRequestID): void
    {
        try {
            $orderRequest = $this->getOrderRequestService()->cancelOrderRequestByVendorID($this->vendor->id, $orderRequestID);
        } catch (FailedCancelOrderRequestException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        $bot->sendMessage("Вы отказались от заказа {$orderRequest->order_id}", $bot->chatId());

        $this->handleShowNewRequestByOne($bot);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handleCancelDiscountRequest(Nutgram $bot): void
    {
        try {
            $orderRequest = $this->getOrderRequestService()->cancelDiscountRequest($this->vendor->id, $this->orderRequestIDToGiveDiscount);
        } catch (FailedCancelDiscountRequestException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        $bot->sendMessage("Вы не дали скидку на заказ {$orderRequest->order_id}", $bot->chatId());

        $this->handleShowDiscountRequestsByOne($bot);
    }

    protected array $discounts = [
        "1.0", "2.0", "3.0", "4.0", "5.0",
    ];

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handleShowDiscountRequestsByOne(Nutgram $bot, string $page = "1"): void
    {
        $this->clearButtons();
        $paginator = $this->getOrderRequestService()->getClientWantDiscountOrderRequestsPaginate($this->vendor->id, (int)$page);
        if ($paginator->isEmpty()) {
            $this->menuText("Больше откликов нет");
            $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: "@handleOrders"));
            $this->showMenu(reopen: true);
            return;
        }

        /** @var OrderRequest $orderRequest */
        $orderRequest = $paginator->items()[0];
        $order = $orderRequest->order;
        $material = $this->getMaterialService()->firstMaterial($order->material_id);
        $delivery = $this->getDeliveryService()->firstDeliveryByOrderID($order->id);
        $vendorStorage = $this->getVendorStorageService()->firstVendorStorage($this->vendor->id, $orderRequest->vendor_storage_id);

        $msg = $this->makeShowDiscountRequestMessage($orderRequest, $order, $material, $delivery, $vendorStorage);
        $bot->sendMessage($msg);
        $this->orderRequestIDToGiveDiscount = $orderRequest->id;

        $this->addButtonRow(InlineKeyboardButton::make("Без скидки", callback_data: "@handleCancelDiscountRequest"));
        $this->addButtonRow(InlineKeyboardButton::make("Дать скидку", callback_data: "@handleShowDiscountOptions"));

        $this->addButtonRow(
            InlineKeyboardButton::make(
                "Заказ {$paginator->currentPage()} из {$paginator->total()}",
                callback_data: "@test"
            )
        );

        $this->menuText("Выберите действие:");
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     */
    function handleShowDiscountOptions(Nutgram $bot): void
    {
        $this->clearButtons();

        $this->addButtonRow(
            InlineKeyboardButton::make($this->discounts[0] . "%", callback_data: "{$this->discounts[0]}@handleGivePercentDiscount"),
            InlineKeyboardButton::make($this->discounts[1] . "%", callback_data: "{$this->discounts[1]}@handleGivePercentDiscount")
        );
        $this->addButtonRow(
            InlineKeyboardButton::make($this->discounts[2] . "%", callback_data: "{$this->discounts[2]}@handleGivePercentDiscount"),
            InlineKeyboardButton::make($this->discounts[3] . "%", callback_data: "{$this->discounts[3]}@handleGivePercentDiscount"),
            InlineKeyboardButton::make($this->discounts[4] . "%", callback_data: "{$this->discounts[4]}@handleGivePercentDiscount")
        );
        $this->addButtonRow(InlineKeyboardButton::make("Ввести свою скидку", callback_data: "@handelGiveCustomPercentDiscount"));
        $this->addButtonRow(InlineKeyboardButton::make("Ввести свою стоимость", callback_data: "@handelGiveNumberDiscount"));

        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: "@handleOrders"));
        $this->menuText("Выберите действие:");
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handelGiveCustomPercentDiscount(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText("Напишите цифрой свой размер скидки по заказу. Пример 4.62");
        $this->orNext('nextSubmitPercentDiscount');
        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function nextSubmitPercentDiscount(Nutgram $bot): void
    {
        $errMsg = $this->validateCustomPercentDiscount($bot->message()->text);
        if (!is_null($errMsg)) {
            $bot->sendMessage($errMsg);
            $this->handelGiveCustomPercentDiscount($bot);
            return;
        }

        $discount = floatval(trim($bot->message()->text));
        $this->discountPercents = round($discount, 2);

        $orderRequest = $this->getOrderRequestService()->firstOrderRequest($this->orderRequestIDToGiveDiscount);
        if (is_null($orderRequest)) {
            $this->start($bot);
            return;
        }
        $discount = $this->getOrderRequestService()
            ->calculateNewPriceByPercentsDiscount(
                $orderRequest->material_price, $orderRequest->delivery_price, $this->discountPercents
            );

        $this->addButtonRow(InlineKeyboardButton::make("Подтверждаю", callback_data: "@handleGivePercentDiscount"));
        $this->addButtonRow(InlineKeyboardButton::make("Изменить скидку", callback_data: "@handleShowDiscountRequestsByOne"));
        $msg = $this->makeSubmitDiscountMessage($orderRequest, $discount);
        $this->menuText($msg);

        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function handelGiveNumberDiscount(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText("Напишите свою стоимость заказа ИТОГО. Пример: 107485");
        $this->orNext('nextSubmitNumberDiscount');
        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @return void
     * @throws InvalidArgumentException
     */
    function nextSubmitNumberDiscount(Nutgram $bot): void
    {
        $err = $this->validateIntAnswer($bot->message()->text);
        if (!is_null($err)) {
            $bot->sendMessage($err);
            $this->handelGiveNumberDiscount($bot);
            return;
        }

        $numberDiscount = intval(trim($bot->message()->text));

        $orderRequest = $this->getOrderRequestService()->firstOrderRequest($this->orderRequestIDToGiveDiscount);
        if (is_null($orderRequest)) {
            $this->start($bot);
            return;
        }
        $currentPrice = $orderRequest->material_price + $orderRequest->delivery_price;

        $err = $this->validateDiscountNumber($currentPrice, $numberDiscount);
        if (!is_null($err)) {
            $bot->sendMessage($err);
            $this->handelGiveNumberDiscount($bot);
            return;
        }
        $discountPercents = $this->getOrderRequestService()
            ->calculateNewPriceByNumberDiscount(
                $orderRequest->material_price, $orderRequest->delivery_price, $numberDiscount
            );
        $discount = $this->getOrderRequestService()
            ->calculateNewPriceByPercentsDiscount(
                $orderRequest->material_price, $orderRequest->delivery_price, $discountPercents
            );

        $this->discountPercents = $discountPercents;

        $this->addButtonRow(InlineKeyboardButton::make("Подтверждаю", callback_data: "@handleGivePercentDiscount"));
        $this->addButtonRow(InlineKeyboardButton::make("Изменить скидку", callback_data: "@handleShowDiscountRequestsByOne"));
        $msg = $this->makeSubmitDiscountMessage($orderRequest, $discount);
        $this->menuText($msg);

        $this->showMenu(true);
    }

    /**
     * @param Nutgram $bot
     * @param string $discount
     * @return void
     * @throws InvalidArgumentException
     */
    function handleGivePercentDiscount(Nutgram $bot, string $discount): void
    {
        if (empty($discount) && is_null($this->discountPercents)) {
            $this->handleShowDiscountRequestsByOne($bot);
            return;
        }

        if (!is_null($this->discountPercents)) {
            $discount = $this->discountPercents;
        }

        try {
            $orderRequest = $this->getOrderRequestService()->givePercentDiscountToOffer(
                $this->vendor->id,
                $this->orderRequestIDToGiveDiscount,
                floatval($discount)
            );
        } catch (FailedMakeDiscountForOrderException $e) {
            // TODO:: реализация на не запланированное поведение
            return;
        }
        if (is_null($orderRequest)) {
            $this->handleShowDiscountRequestsByOne($bot);
            return;
        }
        $bot->sendMessage("Вы дали скидку {$discount}%.");

        $this->handleShowDiscountRequestsByOne($bot);
    }

    /**
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleShowLoadingRequestsByOne(Nutgram $bot, string $page = "1"): void
    {
        $this->clearButtons();
        $requestsWithOrders = $this->getOrderRequestService()
            ->getRequestsWithLoadingOrders($this->vendor->id, (int)$page, 1);

        if (count($requestsWithOrders) == 0) {
            $this->menuText('На данный момент заказов в статусе "погрузка" нет.');
            $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
            $this->showMenu();
            return;
        }

        /** @var OrderRequest $request */
        foreach ($requestsWithOrders as $request) {
            $msg = $this->makeMessage($request, $request->order);
            $bot->sendMessage($msg, reply_markup: InlineKeyboardMarkup::make()->addRow(InlineKeyboardButton::make("123", callback_data: "@test")));
        }

        $this->makeList($requestsWithOrders, "handleShowLoadingRequestsByOne");

        $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
        $this->menuText("Выберите заказ:");
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleShowOnTheWayOrdersByOne(Nutgram $bot, string $page = "1"): void
    {
        $this->clearButtons();
        $requestsWithOrders = $this->getOrderRequestService()
            ->getRequestsWithOnTheWayOrders($this->vendor->id, (int)$page, 1);

        if (count($requestsWithOrders) == 0) {
            $this->menuText('На данный момент заказов в пути нет.');
            $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
            $this->showMenu();
            return;
        }

        /** @var OrderRequest $request */
        foreach ($requestsWithOrders as $request) {
            $msg = $this->makeMessage($request, $request->order);
            $bot->sendMessage($msg, reply_markup: InlineKeyboardMarkup::make()->addRow(InlineKeyboardButton::make("123", callback_data: "@test")));
        }

        $this->makeList($requestsWithOrders, "handleShowOnTheWayOrdersByOne");

        $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
        $this->menuText("Выберите заказ:");
        $this->showMenu(reopen: true);
    }

    /**
     * @param Nutgram $bot
     * @param string $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleShowWaitingDocumentsRequestsByOne(Nutgram $bot, string $page = "1"): void
    {
        $this->clearButtons();
        $requestsWithOrders = $this->getOrderRequestService()
            ->getWaitingDocumentsRequestsWithOrders($this->vendor->id, (int)$page);

        if (count($requestsWithOrders) == 0) {
            $this->menuText('На данный момент заказов ожидающих документов нет.');
            $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
            $this->showMenu();
            return;
        }

        /** @var OrderRequest $request */
        foreach ($requestsWithOrders as $request) {
            $msg = $this->makeMessage($request, $request->order);
            $bot->sendMessage($msg, reply_markup: InlineKeyboardMarkup::make()->addRow(InlineKeyboardButton::make("123", callback_data: "@test")));
        }

        $this->makeList($requestsWithOrders, "handleShowWaitingDocumentsRequestsByOne");

        $this->addButtonRow(InlineKeyboardButton::make("Назад", callback_data: "@handleOrders"));
        $this->menuText("Выберите заказ:");
        $this->showMenu(reopen: true);
    }

    /**
     * @return void
     */
    public function addBackToStartButton(): void
    {
        $this->addButtonRow(InlineKeyboardButton::make("Вернуться в меню", callback_data: "@start"));
    }

    /**
     * @param Nutgram $bot
     * @return void
     */
    function backWithFileDelete(Nutgram $bot): void
    {
        $bot->deleteMessage($bot->chatId(), $this->fileMessageID);
        $this->start($bot);
    }

    function test()
    {

    }


    /**
     * Формирует сообщение о новом заказе для поставщика.
     *
     * @param OrderRequest $orderRequest
     * @param Order $order
     * @param Delivery $delivery
     * @param Material $material
     * @param VendorStorage $vendorStorage
     * @return string
     */
    protected function makeNewRequestMessage(
        OrderRequest $orderRequest,
        Order $order,
        Delivery $delivery,
        Material $material,
        VendorStorage $vendorStorage
    ): string
    {
        $totalPrice = $this->getOrderRequestService()->calculateTotalSelfPriceForOffer(
            $material->id,
            $vendorStorage->id,
            $orderRequest->distance,
            (int) $order->quantity
        );

        $msg = "Склад отгрузки - {$vendorStorage->address}" . PHP_EOL;
        $msg .= " Товары и количество - {$material->name} {$order->quantity} м³" . PHP_EOL;
        $msg .= "Дата доставки - с " . date('d.m.Y', strtotime($delivery->wanted_delivery_window_start)) .
            " по " . date('d.m.Y', strtotime($delivery->wanted_delivery_window_end)) . PHP_EOL;
        $msg .= "Время доставки - с " . date('H:i', strtotime($delivery->wanted_delivery_window_start)) .
            " до " . date('H:i', strtotime($delivery->wanted_delivery_window_end)) . PHP_EOL;
        $msg .= " Расстояние между складом и точкой доставки, рассчитанное сервисом - {$orderRequest->distance} км" . PHP_EOL;
        $msg .= PHP_EOL;
        $msg .= "Время в пути - {$orderRequest->delivery_duration_minutes} минут" . PHP_EOL;
        $msg .= "Адрес доставки - {$delivery->address}" . PHP_EOL;
        $msg .= " Итоговая стоимость:" . PHP_EOL;
        $msg .= " Ваша цена - {$totalPrice}" . PHP_EOL;
        $msg .= " СРЦ региона - Не определено" . PHP_EOL; // TODO

        return $msg;
    }

    /**
     * @param OrderRequest $orderRequest
     * @param Order $order
     * @param Material $material
     * @param Delivery $delivery
     * @param VendorStorage $vendorStorage
     * @return string
     */
    protected function makeShowDiscountRequestMessage(
        OrderRequest  $orderRequest,
        Order         $order,
        Material      $material,
        Delivery      $delivery,
        VendorStorage $vendorStorage,
    ): string
    {
        $totalPrice = $this->getOrderRequestService()->calculateTotalSelfPriceForOffer(
            $material->id,
            $vendorStorage->id,
            $orderRequest->distance,
            (int) $order->quantity
        );
        $msg = "Заказ № - {$orderRequest->id}" . PHP_EOL;
        $msg .= "Склад отгрузки - {$vendorStorage->address}" . PHP_EOL;
        $msg .= "Товары и количество - {$material->name} {$order->quantity} м³" . PHP_EOL;
        $msg .= "Дата доставки - с " . date('d.m.Y', strtotime($delivery->wanted_delivery_window_start)) .
            " по " . date('d.m.Y', strtotime($delivery->wanted_delivery_window_end)) . PHP_EOL;
        $msg .= "Время доставки - с " . date('H:i', strtotime($delivery->wanted_delivery_window_start)) .
            " до " . date('H:i', strtotime($delivery->wanted_delivery_window_end)) . PHP_EOL;
        $msg .= "Расстояние между складом и точкой доставки, рассчитанное сервисом - {$orderRequest->distance} км" . PHP_EOL;
        $msg .= "Время в пути - {$orderRequest->delivery_duration_minutes} минут" . PHP_EOL;
        $msg .= "Адрес доставки - {$delivery->address}" . PHP_EOL;
        $msg .= "Итого стоимость:" . PHP_EOL;
        $msg .= "Ваш прайс - {$totalPrice}" . PHP_EOL;
        $msg .= "СРЦ региона - ничего пока" . PHP_EOL; // TODO
        $msg .= "************************************" . PHP_EOL;
        $msg .= "Ваше предложение - {$totalPrice}" . PHP_EOL;

        return $msg;
    }

    /**
     * @param OrderRequest $orderRequest
     * @param int $discount
     * @return string
     */
    protected function makeSubmitDiscountMessage(
        OrderRequest $orderRequest,
        int          $discount,
    ): string
    {
        $totalPrice = $orderRequest->material_price + $orderRequest->delivery_price;

        $msg = "Ваше нынешнее предложение: {$totalPrice}" . PHP_EOL;
        $msg .= "Ваше новое предложение: {$discount}" . PHP_EOL;
        $msg .= "Скидка {$this->discountPercents}%" . PHP_EOL;

        return $msg;
    }

    /**
     * @param Nutgram $bot
     * @param int $page
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleShowCompletedOrderRequests(Nutgram $bot, $page = 5): void
    {
        $this->clearButtons();
        $completedOrderRequests = $this->getOrderRequestService()->getCompletedOrderRequestsPaginate(
            $this->vendor->id,
            (int)$page
        );
        if ($completedOrderRequests->isEmpty()) {
            $this->handleEmptyOrderRequests();
            return;
        }
        $this->handleNotEmptyOrderRequests($bot, $completedOrderRequests);
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function handleEmptyOrderRequests(): void
    {
        $this->menuText('У вас нет заказов в архиве!');
        $this->addButtonRow(InlineKeyboardButton::make($this->backButton, callback_data: '@start'));
        $this->showMenu();
    }

    /**
     * @param Nutgram $bot
     * @param LengthAwarePaginator $completedOrderRequests
     * @return void
     * @throws InvalidArgumentException
     */
    private function handleNotEmptyOrderRequests(Nutgram $bot, LengthAwarePaginator $completedOrderRequests): void
    {
        $this->menuText('Ваш список товаров');
        /** @var OrderRequest $orderRequest */
        foreach ($completedOrderRequests as $orderRequest) {
            $message = $this->makeMessage(
                $orderRequest,
                $orderRequest->order,
                $orderRequest->order->delivery,
                $orderRequest->order->material,
                $orderRequest->order->vendorStorage
            );
            $bot->sendMessage('Ваши заказы в архиве:' . PHP_EOL . $message);
        }
        $this->makeList($completedOrderRequests, 'handleShowCompletedOrderRequests');
        $this->menuText('Ваш список товаров');
        $this->showMenu(true);
    }

    /**
     * Функция возвращает склад заказчика.
     *
     * @param integer $storageID
     * @return VendorStorage|null
     */
    private function getVendorStorage(int $storageID): ?VendorStorage
    {
        $this->clearButtons();
        $vendorID = $this->vendor->id;
        $vendorStorage = $this->getVendorStorageService()->firstVendorStorage($vendorID, $storageID);

        if (is_null($vendorStorage)) {
            $this->menuText('Склад не существует');
            $this->showMenu();
            return null;
        }
        return $vendorStorage;
    }

}
