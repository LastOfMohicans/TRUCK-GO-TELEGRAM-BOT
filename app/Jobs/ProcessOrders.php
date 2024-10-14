<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\VendorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public VendorService $vendorService;

    /**
     * Create a new job instance.
     */
    public function __construct(VendorService $vendorService)
    {
        dd($vendorService);
        //$this->vendorService = $vendorService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /* $vendors = $this->vendorService->GetVendorsSearchingOrders();

         echo($vendors);*/
    }
}
