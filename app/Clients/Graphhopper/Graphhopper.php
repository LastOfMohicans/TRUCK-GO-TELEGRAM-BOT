<?php
declare(strict_types=1);

namespace App\Clients\Graphhopper;

use App\Clients\Route;
use App\Contracts\RouteInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;


/**
 * Клиента сервиса Graphhopper. Используем для получения пути.
 */
class Graphhopper implements RouteInterface
{
    protected string $apiKey = "";

    /**
     * Для какого типа машины строить путь.
     *
     * @var string
     */
    protected string $profile = "truck";

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $fromLat
     * @param string $fromLong
     * @param string $toLat
     * @param string $toLong
     * @return ?Route
     * @throws ConnectionException
     */
    public function getRoute(string $fromLat, string $fromLong, string $toLat, string $toLong): ?Route
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://graphhopper.com/api/1/route" . "?key={$this->apiKey}", [
            'points' => [
                [
                    $fromLong, $fromLat, // Порядок передачи должен быть таким, что latitude идет после.
                ],
                [
                    $toLong, $toLat,
                ],

            ],
            'profile' => 'truck',
            'elevation' => false,
            'instructions' => false,
            'points_encoded' => false,
        ]);

        if ($response->failed()) {
            return null;
        }

        $json = $response->json();

        if (count($json) < 3 || !isset($json['paths'])) {
            return null;
        }

        $paths = $json['paths'];
        if (count($paths) < 1) {
            return null;
        }

        $path = $paths[0];
        $time = $path['time']; // В миллисекундах.
        $time = intval($time / 1000 / 60);

        return new Route(intval($path['distance']), $time);
    }

}
