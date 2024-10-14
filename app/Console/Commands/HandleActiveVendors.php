<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MaterialService;
use App\Services\OrderService;
use App\Services\VendorStorageService;
use Illuminate\Console\Command;

class HandleActiveVendors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:handle-active-vendors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(VendorStorageService $vendorStorageService,
                           MaterialService $materialsService,
                           OrderService         $orderService): void
    {

        $vendorStorageService->RunAlgorithmToFindVendorsForOrders();
    }
}
