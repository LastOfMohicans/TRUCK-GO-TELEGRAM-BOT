<?php
declare(strict_types=1);

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use Throwable;

/**
 * Управляет complaint сущностью в БД.
 */
interface ComplaintRepositoryInterface
{
    /**
     * Создать жалобу.
     *
     * @param Complaint $complaint $
     * @return Complaint
     * @throws Throwable
     */
    public function create(Complaint $complaint): Complaint;
}
