<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\Material;
use App\Models\Order;
use App\Models\StorageMaterial;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\StorageMaterial\StorageMaterialRepositoryInterface;
use App\Telegram\TelegramVendorClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;

class NotifyVendorAboutAvailableOrderInTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string[]
     */
    protected array $orderIDs;
    protected string $vendorChatID;
    protected array $orderIDToDistance;
    protected int $vendorStorageID;

    /**
     * @param string[] $orderIDs
     * @param string $vendorChatID
     * @param array $orderIDToDistance
     * @param int $vendorStorageID
     */
    public function __construct(array $orderIDs, string $vendorChatID, array $orderIDToDistance, int $vendorStorageID)
    {
        $this->orderIDs = $orderIDs;
        $this->vendorChatID = $vendorChatID;
        $this->orderIDToDistance = $orderIDToDistance;
        $this->vendorStorageID = $vendorStorageID;
    }

    public function handle(
        TelegramVendorClient               $bot,
        OrderRepositoryInterface           $orderRepository,
        StorageMaterialRepositoryInterface $storageMaterialRepository
    )
    {
        $orders = $orderRepository->get($this->orderIDs);
        $orders = $orders->all();

        $storageMaterials = $storageMaterialRepository->getByVendorStorageID($this->vendorStorageID);
        $storageMaterialByMaterialID = $storageMaterials->mapWithKeys(function ($item) {
            return [$item->material_id => $item];
        });

        if (count($orders) == 0) {
            return;
        }

        $chatID = $this->vendorChatID;
        /** @var Order $order */
        foreach ($orders as $order) {
            /** @var Delivery $delivery */
            $delivery = $order->delivery;
            /** @var Material $material */
            $material = $order->material;

            $distance = $this->orderIDToDistance[$order->id];

            /** @var StorageMaterial $storageMat */
            $storageMat = $storageMaterialByMaterialID[$order->material_id];
            $totalPrice = 0;
            $totalPrice += $storageMat->cubic_meter_price * $order->quantity;
            $totalPrice += $storageMat->delivery_cost_per_cubic_meter_per_kilometer * $distance;

            try {
                $bot->sendMessage(
                    text: (string)view(
                        'telegram.order_request_created_notification',
                        [
                            'order' => $order,
                            'delivery' => $delivery,
                            'materialName' => $material->name,
                            'distance' => $distance,
                            'totalPrice' => $totalPrice,
                        ],
                    ),
                    chat_id: $chatID
                );
            } catch (TelegramException $e) {
                if (!$e->getMessage() == "Bad Request: chat not found") {
                    report($e);
                }
            }
        }
    }
}
