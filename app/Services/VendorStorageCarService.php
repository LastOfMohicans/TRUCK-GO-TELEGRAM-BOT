<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\VendorStorageCar\VendorStorageCarRepositoryInterface;

class VendorStorageCarService
{
    protected VendorStorageCarRepositoryInterface $repository;

    public function __construct(VendorStorageCarRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

}
