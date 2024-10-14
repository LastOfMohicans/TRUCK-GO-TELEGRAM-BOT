<?php
declare(strict_types=1);

namespace App\Exports\Storages;


use App\Models\Vendor;
use Illuminate\Support\Collection;

class CreateStoragesExport extends StoragesExportBase
{
    protected string $reason = 'Заведение поставщика';

    public function __construct(Collection $materials, Vendor $vendor)
    {
        parent::__construct($materials, $vendor);
    }
}

