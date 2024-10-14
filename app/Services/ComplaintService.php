<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FailedToCreateComplaintException;
use App\Models\complaint;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use Throwable;

class ComplaintService
{
    protected ComplaintRepositoryInterface $complaintRepository;

    public function __construct(ComplaintRepositoryInterface $complaintRepository)
    {
        $this->complaintRepository = $complaintRepository;
    }

    /**
     * Создает жалобу.
     *
     * @param complaint $complaint
     * @return complaint
     * @throws FailedToCreateComplaintException
     */
    public function createComplaint(Complaint $complaint): Complaint
    {
        try {
            return $this->complaintRepository->create($complaint);
        } catch (Throwable $e) {
            report($e);
            throw new FailedtoCreateComplaintException();
        }
    }
}
