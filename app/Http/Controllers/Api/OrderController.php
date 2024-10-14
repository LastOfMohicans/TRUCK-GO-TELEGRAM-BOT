<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClientService;
use App\Services\MaterialQuestionsService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    protected ClientService $clientService;
    protected OrderService $orderService;
    protected MaterialQuestionsService $materialQuestionsService;


    public function __construct(
        OrderService             $orderService,
        ClientService $userService,
        MaterialQuestionsService $materialQuestionsService,
    )
    {
        $this->orderService = $orderService;
        $this->clientService = $userService;
        $this->materialQuestionsService = $materialQuestionsService;
    }

    public function create(Request $request)
    {
        /**
         * пока что занимаемся реализацией только бот части
         */
        /* $request->validate([
             'material_id' => 'required|integer',
         ]);

         $user = $this->userService->getUser();


         пока что забиваем на
         $order = $this->orderService->createOrder(
             $request->material_id,
             $user,
         );

         return new OrderResource($order);*/
    }

}
