<?php
declare(strict_types=1);

namespace App\Telegram\Traits;


use App\Enums\ComplaintReason;
use App\Enums\ComplaintSeverity;
use App\Exceptions\FailedToCreateComplaintException;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Support\Facades\Auth;
use SergiX44\Nutgram\Nutgram;

/**
 * Трейт для создания жалоб на нужную причину.
 */
trait Complaintable
{
    /**
     * Определятся какой метод вызвать после успешного завершения отправки жалобы.
     *
     * @var string
     */
    public string $callbackMethodName;

    protected ComplaintService $complaintService;

    /**
     * Инициализируем трейт, обязательно для корректной работы.
     *
     * @param ComplaintService $complaintService
     * @return void
     */
    public function initializeComplaintableTrait(ComplaintService $complaintService): void
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Обрабатывает жалобу смены ответственного поставщика.
     *
     * @param Nutgram $bot
     * @param string $callbackMethodName
     * @return void
     */
    public function handleChangeMainVendorWhenINNExists(Nutgram $bot, string $callbackMethodName): void
    {
        $this->callbackMethodName = $callbackMethodName;

        $complaint = new Complaint();

        $complaint->complaint_reason_id = ComplaintReason::INNExistsAndWannaChange;
        $complaint->vendor_id = Auth::user()->id;
        $complaint->severity = ComplaintSeverity::Medium;

        $this->createComplaint($bot, $complaint);
        $this->callCallbackMethod($bot);
    }

    /**
     * Внутренний общий метод создания жалобы.
     *
     * @param Nutgram $bot
     * @param Complaint $complaint
     * @return void
     */
    protected function createComplaint(Nutgram $bot, Complaint $complaint): void
    {
        try {
            $this->complaintService->createComplaint($complaint);
        } catch (FailedToCreateComplaintException $e) {
            // TODO:: реализация на не запланированное поведение
            $bot->sendMessage($e->getMessage());
            return;
        }
    }


    /**
     * Метод вызова калбека.
     *
     * @param Nutgram $bot
     * @return void
     */
    protected function callCallbackMethod(Nutgram $bot): void
    {
        $this->{$this->callbackMethodName}($bot);
    }
}
