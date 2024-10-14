<?php
declare(strict_types=1);

namespace App\Clients;


/**
 * Сущность адреса.
 *
 * @property int $distance_km                                            Дистанция между точками в километрах.
 * @property int $distance_m                                             Дистанция между точками в метрах.
 * @property int $time_in_minutes                                        Время пути от точки до точки в минутах.
 */
class Route
{
    protected int $distance_km;
    protected int $distance_m;
    protected int $time_in_minutes;

    /**
     * @param int $distance_m
     * @param int $time_in_minutes
     */
    public function __construct(
        int $distance_m,
        int $time_in_minutes,
    )
    {
        $this->distance_m = $distance_m;
        $this->distance_km = $this->convertMetersToKM($distance_m);
        $this->time_in_minutes = $time_in_minutes;
    }

    /**
     * Переводим метры в километры.
     *
     * @param int $meters
     * @return int
     */
    protected function convertMetersToKM(int $meters): int
    {
        return intval($meters / 1000);
    }

    public function getDistanceKm(): int
    {
        return $this->distance_km;
    }

    public function getDistanceM(): int
    {
        return $this->distance_m;
    }

    public function getTimeInMinutes(): int
    {
        return $this->time_in_minutes;
    }

}
