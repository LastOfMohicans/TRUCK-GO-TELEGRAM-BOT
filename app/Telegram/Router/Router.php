<?php

declare(strict_types=1);

namespace App\Telegram\Router;

class Router
{
    public readonly ClientRoutes $client;
    public readonly VendorRoutes $vendor;
    public readonly DriverRoutes $driver;

    public function __construct()
    {
        $this->client = new ClientRoutes();
        $this->vendor = new VendorRoutes();
        $this->driver = new DriverRoutes();
    }
}
