<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Clients\Route;

/**
 * Интерфейс получения данных о пути между точками.
 */
interface RouteInterface
{
    /**
     * Получение пути между точками.
     * Возвращает null, если запрос не успешен.
     *
     * @param string $fromLat
     * @param string $fromLong
     * @param string $toLat
     * @param string $toLong
     * @return Route|null
     */
    public function getRoute(string $fromLat, string $fromLong, string $toLat, string $toLong): ?Route;

}
