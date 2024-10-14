<?php

namespace Tests\Services;

use App\Services\OrderRequestService;
use PHPUnit\Framework\TestCase;

class OrderRequestServiceTest extends TestCase
{

    public function testCalculateNewPriceByNumberDiscount()
    {
        $orderRequestService = new OrderRequestService(null, null);

        $this->assertEquals(70, $orderRequestService->calculateNewPriceByNumberDiscount(150, 50, 60));
        $this->assertEquals(90, $orderRequestService->calculateNewPriceByNumberDiscount(100, 0, 10));
        $this->assertEquals(36.64, $orderRequestService->calculateNewPriceByNumberDiscount(333, 0, 211));
        $this->assertEquals(91.35, $orderRequestService->calculateNewPriceByNumberDiscount(1156, 0, 100));

        $this->assertEquals(100, $orderRequestService->calculateNewPriceByPercentsDiscount(1156, 0, 91.35));
    }
}
