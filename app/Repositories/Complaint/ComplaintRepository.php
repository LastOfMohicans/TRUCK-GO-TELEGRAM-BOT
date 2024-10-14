<?php
declare(strict_types=1);

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use Throwable;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    /**
     * @param Complaint $complaint
     * @return Complaint
     * @throws Throwable
     */
    public function create(Complaint $complaint): Complaint
    {
        $complaint->saveOrFail();

        return $complaint;
    }
}
