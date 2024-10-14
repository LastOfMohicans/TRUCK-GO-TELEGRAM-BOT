<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderRequest;
use App\Models\OrderRequestHistory;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class TelegramTestController extends Controller
{
    public function removeClientAndHisData()
    {
        /** @var Client $client */
        $client = Auth::user();

        if (!$client) {
            return;
        }

        $orders = $client->orders()->get();

        DB::beginTransaction();
        try {
            $complaints = $client->complaints()->get();
            foreach ($complaints as $complaint) {
                $complaint->forceDelete();
            }

            if ($orders->count() > 0) {
                /** @var Order $order */
                foreach ($orders as $order) {
                    $delivery = $order->delivery();
                    if ($delivery->exists()) {
                        $delivery->delete();
                    }
                    $orderHistories = $order->orderHistory()->get();

                    /** @var OrderHistory $orderHistory */
                    foreach ($orderHistories as $orderHistory) {
                        $orderHistory->delete();
                    }

                    $answers = $order->orderQuestionAnswers()->get();
                    foreach ($answers as $answer) {
                        $answer->forceDelete();
                    }

                    $orderRequests = $order->orderRequests()->get();
                    /** @var OrderRequest $orderRequest */
                    foreach ($orderRequests as $orderRequest) {
                        $orderRequestHistories = $orderRequest->orderRequestHistories()->get();

                        /** @var OrderRequestHistory $orderRequestHistory */
                        foreach ($orderRequestHistories as $orderRequestHistory) {
                            $orderRequestHistory->delete();
                        }
                        $orderRequest->forceDelete();
                    }
                }
            }

            /** @var Order $order */
            foreach ($orders as $order) {
                $order->forceDelete();
            }

            $client->delete();
            Auth::forgetUser();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
        }
    }

    public function removeVendorAndHisData()
    {
        /** @var Vendor $vendor */
        $vendor = Auth::user();

        if (!$vendor) {
            return;
        }

        DB::beginTransaction();
        try {
            $vendorId = $vendor->id;

            DB::table('order_request_histories')
                ->join('order_requests', 'order_request_histories.order_request_id', '=', 'order_requests.id')
                ->where('order_requests.vendor_id', $vendorId)
                ->delete();

            DB::table('order_requests')->where('vendor_id', $vendorId)->delete();

            DB::table('complaints')->where('vendor_id', $vendorId)->delete();

            $vendorStorages = DB::table('vendor_storages')->where('vendor_id', $vendorId)->get();

            foreach ($vendorStorages as $storage) {
                DB::table('storage_materials')->where('vendor_storage_id', $storage->id)->delete();
            }

            DB::table('vendor_storages')->where('vendor_id', $vendorId)->delete();

            $vendor->forceDelete();
            Auth::forgetUser();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
        }
    }
}
