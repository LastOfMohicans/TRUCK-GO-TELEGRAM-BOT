<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Route;

/**
 * Мок для построения пути.
 */
class RouteMock implements RouteInterface
{

    /**
     * @param string $fromLat
     * @param string $fromLong
     * @param string $toLat
     * @param string $toLong
     * @return ?Route
     */
    public function getRoute(string $fromLat, string $fromLong, string $toLat, string $toLong): ?Route
    {
        return new Route(1430, intval(ceil(1140696 / 1000 / 60)));
    }
}
