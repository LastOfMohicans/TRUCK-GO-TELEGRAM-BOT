<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class CoordinateService
{
    /**
     * Правило для валидации из файла долготы и широты.
     *
     * @var array|string[]
     */
    protected array $rulesToParsingCoordinates = [
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ];

    /**
     * Парсит значение и возвращается либо массив координат, либо false.
     *
     * @param string $coordinates
     * @return false|array
     */
    public function parseCoordinates(string $coordinates): false|array
    {
        $coordinates = $this->extractCoordinates($coordinates);
        if (count($coordinates) != 2) {
            return false;
        }

        $address = ['latitude' => $coordinates[0], 'longitude' => $coordinates[1]];
        $validator = Validator::make($address, $this->rulesToParsingCoordinates);

        if ($validator->fails()) {
            return false;
        }
        $address = [
            'latitude' => (float)$address['latitude'],
            'longitude' => (float)$address['longitude'],
        ];

        return $address;
    }

    /**
     * Возвращает true, если строка содержит корректные координаты, иначе false.
     *
     * @param string $value
     * @return bool
     */
    public function isCoordinates(string $value): bool
    {
        return $this->parseCoordinates($value) != false;
    }

    /**
     * Превращает строку в массив через делитель.
     *
     * @param string $coordinates
     * @return array
     */
    private function extractCoordinates(string $coordinates): array
    {
        return preg_split('/[\s,]+/', trim($coordinates));
    }
}
